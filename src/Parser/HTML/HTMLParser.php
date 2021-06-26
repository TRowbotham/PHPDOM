<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentReadyState;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLFormElement;
use Rowbot\DOM\Element\HTML\HTMLIFrameElement;
use Rowbot\DOM\Element\HTML\HTMLScriptElement;
use Rowbot\DOM\Element\HTML\HTMLStyleElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Element\HTML\HTMLTextAreaElement;
use Rowbot\DOM\Element\HTML\HTMLTitleElement;
use Rowbot\DOM\Environment;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\NodeList;
use Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack;
use Rowbot\DOM\Parser\Collection\OpenElementStack;
use Rowbot\DOM\Parser\HTML\InsertionMode\InTemplateInsertionMode;
use Rowbot\DOM\Parser\Parser;
use Rowbot\DOM\Parser\Token\AttributeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use SplObjectStorage;

use function mb_check_encoding;
use function mb_convert_encoding;
use function mb_substitute_character;
use function preg_match;
use function str_replace;

class HTMLParser extends Parser
{
    /**
     * The tokenizer associated with the parser.
     *
     * @var \Rowbot\DOM\Parser\HTML\Tokenizer
     */
    private $tokenizer;

    /**
     * The treebuilder associated with the parser.
     *
     * @var \Rowbot\DOM\Parser\HTML\TreeBuilder
     */
    private $treeBuilder;

    public function __construct(
        Document $document,
        bool $isFragmentCase = false,
        Element $contextElement = null
    ) {
        parent::__construct();

        $context = new ParserContext(
            $contextElement,
            new OpenElementStack(),
            $this->inputStream,
            $isFragmentCase,
            $document->isScriptingEnabled()
        );
        $this->tokenizer = new Tokenizer($context);
        $context = new TreeBuilderContext(
            $document,
            $context,
            new ActiveFormattingElementStack(),
            new SplObjectStorage()
        );
        $this->treeBuilder = new TreeBuilder($context);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#abort-a-parser
     */
    public function abort(): void
    {
        // Throw away any pending content in the input stream, and discard any
        // future content that would have been added to it.
        $this->inputStream->discard();

        $context = $this->treeBuilder->getContext();

        // Set the current document readiness to "interactive"
        $context->document->setReadyState(DocumentReadyState::INTERACTIVE);

        // Pop all the nodes off the stack of open elements.
        $context->parser->openElements->clear();

        // Set the current document readiness to "complete"
        $context->document->setReadyState(DocumentReadyState::COMPLETE);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-html-fragments
     */
    public static function parseHTMLFragment(string $input, Element $contextElement): NodeList
    {
        // Create a new Document node, and mark it as being an HTML document.
        $contextDocument = $contextElement->getNodeDocument();
        $env = new Environment(null, 'text/html');
        $env->setScriptingEnabled($contextDocument->getEnvironment()->isScriptingEnabled());
        $doc = new HTMLDocument($env);

        // If the node document of the context element is in quirks mode, then
        // let the Document be in quirks mode. Otherwise, the node document of
        // the context element is in limited-quirks mode, then let the Document
        // be in limited-quirks mode. Otherwise, leave the Document in no-quirks
        // mode.
        $doc->setMode($contextDocument->getMode());

        // Create a new HTML parser, and associate it with the just created
        // Document node.
        $parser = new self($doc, true, $contextElement);
        $localName = $contextElement->localName;
        $context = $parser->treeBuilder->getContext();

        // Set the state of the HTML parser's tokenization stage as follows, switching on the context element:
        if ($contextElement instanceof HTMLTitleElement || $contextElement instanceof HTMLTextAreaElement) {
            $context->parser->tokenizerState = TokenizerState::RCDATA;
        } elseif (
            $contextElement instanceof HTMLStyleElement
            || ($contextElement instanceof HTMLElement && $localName === 'xmp')
            || $contextElement instanceof HTMLIFrameElement
            || ($contextElement instanceof HTMLElement && $localName === 'noembed')
            || ($contextElement instanceof HTMLElement && $localName === 'noframes')
        ) {
            $context->parser->tokenizerState = TokenizerState::RAWTEXT;
        } elseif ($contextElement instanceof HTMLScriptElement) {
            $context->parser->tokenizerState = TokenizerState::SCRIPT_DATA;
        } elseif ($contextElement instanceof HTMLElement && $localName === 'noscript') {
            if ($context->parser->isScriptingEnabled) {
                $context->parser->tokenizerState = TokenizerState::RAWTEXT;
            }
        } elseif ($contextElement instanceof HTMLElement && $localName === 'plaintext') {
            $context->parser->tokenizerState = TokenizerState::PLAINTEXT;
        }

        // NOTE: For performance reasons, an implementation that does not report
        // errors and that uses the actual state machine described in this
        // specification directly could use the PLAINTEXT state instead of the
        // RAWTEXT and script data states where those are mentioned in the list
        // above. Except for rules regarding parse errors, they are equivalent,
        // since there is no appropriate end tag token in the fragment case, yet
        // they involve far fewer state transitions.

        // Let root be a new html element with no attributes.
        $root = ElementFactory::create($doc, 'html', Namespaces::HTML);

        // Append the element root to the Document node created above.
        $doc->appendChild($root);

        // Set up the parser's stack of open elements so that it contains just
        // the single element root.
        $context->parser->openElements->push($root);

        // If the context element is a template element, push "in template" onto
        // the stack of template insertion modes so that it is the new current
        // template insertion mode.
        if ($contextElement instanceof HTMLTemplateElement) {
            $context->templateInsertionModes->push(InTemplateInsertionMode::class);
        }

        // Create a start tag token whose name is the local name of context and
        // whose attributes are the attributes of context.
        $token = new StartTagToken($localName);

        foreach ($contextElement->attributes as $attr) {
            $token->attributes[] = new AttributeToken($attr->name, $attr->value);
        }

        // Let this start tag token be the start tag token of the context node,
        // e.g. for the purposes of determining if it is an HTML integration
        // point.
        $context->elementTokenMap->attach($contextElement, $token);

        // Reset the parser's insertion mode appropriately.
        $context->resetInsertionMode();
        $node = $contextElement;

        // Set the parser's form element pointer to the nearest node to the
        // context element that is a form element (going straight up the
        // ancestor chain, and including the element itself, if it is a form
        // element), if any. (If there is no such form element, the form element
        // pointer keeps its initial value, null.)
        while ($node) {
            if ($node instanceof HTMLFormElement) {
                $context->parser->formElementPointer = $node;

                break;
            }

            $node = $node->parentNode;
        }

        // Place the input into the input stream for the HTML parser just
        // created. The encoding confidence is irrelevant.
        $parser->preprocessInputStream($input);

        // Start the parser and let it run until it has consumed all the
        // characters just inserted into the input stream.
        $parser->run();

        // Return the child nodes of root, in tree order.
        return $root->childNodes;
    }

    public function run(): void
    {
        foreach ($this->tokenizer->run() as $token) {
            $isStartTagToken = $token instanceof StartTagToken;

            if ($isStartTagToken) {
                $this->tokenizer->setLastEmittedStartTagToken($token);
            } elseif ($token instanceof EndTagToken) {
                // When an end tag token is emitted with attributes, that is a
                // parse error.
                if ($token->attributes !== []) {
                    // Parse error.
                }

                // When an end tag token is emitted with its self-closing flag
                // set, that is a parse error.
                if ($token->isSelfClosing()) {
                    // Parse error.
                }
            }

            $this->treeBuilder->run($token);

            // When a start tag token is emitted with its self-closing flag set,
            // if the flag is not acknowledged when it is processed by the tree
            // construction stage, that is a parse error.
            if ($isStartTagToken && !$token->wasAcknowledged()) {
                // Parse error.
            }
        }
    }

    /**
     * Preprocesses the input stream.
     */
    public function preprocessInputStream(string $input): void
    {
        $char = mb_substitute_character();
        mb_substitute_character(0xFFFD);
        $input = mb_convert_encoding($input, 'utf-8', 'utf-8');
        mb_substitute_character($char);

        if (
            preg_match(
                '/[\x01-\x08\x0E-\x1F\x7F-\x9F\x{FDD0}-\x{FDEF}\x0B'
                . '\x{FFFE}\x{FFFF}'
                . '\x{1FFFE}\x{1FFFF}'
                . '\x{2FFFE}\x{2FFFF}'
                . '\x{3FFFE}\x{3FFFF}'
                . '\x{4FFFE}\x{4FFFF}'
                . '\x{5FFFE}\x{5FFFF}'
                . '\x{6FFFE}\x{6FFFF}'
                . '\x{7FFFE}\x{7FFFF}'
                . '\x{8FFFE}\x{8FFFF}'
                . '\x{9FFFE}\x{9FFFF}'
                . '\x{AFFFE}\x{AFFFF}'
                . '\x{BFFFE}\x{BFFFF}'
                . '\x{CFFFE}\x{CFFFF}'
                . '\x{DFFFE}\x{DFFFF}'
                . '\x{EFFFE}\x{EFFFF}'
                . '\x{FFFFE}\x{FFFFF}'
                . '\x{10FFFE}\x{10FFFF}]/u',
                $input
            )
        ) {
            // Parse error
        }

        // Any character that is a not a Unicode character, i.e. any isolated
        // surrogate, is a parse error. (These can only find their way into the
        // input stream via script APIs such as document.write().)
        if (!mb_check_encoding($input, 'utf-8')) {
            // Parse error
        }

        // U+000D CARRIAGE RETURN (CR) characters and U+000A LINE FEED (LF)
        // characters are treated specially. Any LF character that immediately
        // follows a CR character must be ignored, and all CR characters must
        // then be converted to LF characters. Thus, newlines in HTML DOMs are
        // represented by LF characters, and there are never any CR characters
        // in the input to the tokenization stage.
        $this->inputStream->append(str_replace(["\r\n", "\r"], "\n", $input));
    }
}
