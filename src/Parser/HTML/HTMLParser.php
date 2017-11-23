<?php
namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentReadyState;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLFormElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack;
use Rowbot\DOM\Parser\Collection\OpenElementStack;
use Rowbot\DOM\Parser\Parser;
use Rowbot\DOM\Parser\TextBuilder;
use Rowbot\DOM\Parser\Token\AttributeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use SplStack;
use SplObjectStorage;

class HTMLParser extends Parser
{
    use ParserOrTreeBuilder;

    /**
     * The tokenizer associated with the parser.
     *
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * The treebuilder associated with the parser.
     *
     * @var TreeBuilder
     */
    private $treeBuilder;

    public function __construct(
        Document $document,
        $isFragmentCase = false,
        $contextElement = null
    ) {
        parent::__construct();

        $this->activeFormattingElements = new ActiveFormattingElementStack();
        $this->contextElement = $contextElement;
        $this->document = $document;
        $this->isFragmentCase = $isFragmentCase;
        $this->isScriptingEnabled = false;
        $this->openElements = new OpenElementStack();
        $this->state = new ParserState();
        $this->templateInsertionModes = new SplStack();
        $this->textBuilder = new TextBuilder();
        $this->tokenRepository = new SplObjectStorage();
        $this->treeBuilder = new TreeBuilder(
            $document,
            $this->activeFormattingElements,
            $this->openElements,
            $this->templateInsertionModes,
            $this->textBuilder,
            $this->tokenRepository,
            $this->isFragmentCase,
            $this->isScriptingEnabled,
            $this->contextElement,
            $this->state
        );
        $this->tokenizer = new Tokenizer(
            $this->inputStream,
            $this->openElements,
            $this->isFragmentCase,
            $this->contextElement,
            $this->state
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#abort-a-parser
     */
    public function abort()
    {
        // Throw away any pending content in the input stream, and discard any
        // future content that would have been added to it.
        $this->inputStream->discard();

        // Set the current document readiness to "interactive"
        $this->document->setReadyState(DocumentReadyState::INTERACTIVE);

        // Pop all the nodes off the stack of open elements.
        $openElements = $this->openElements->clear();

        // Set the current document readiness to "complete"
        $this->document->setReadyState(DocumentReadyState::COMPLETE);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-html-fragments
     *
     * @param string $aInput The markup string to parse
     *
     * @param Element $aContextElement The context for the parser.
     *
     * @return Node[]
     */
    public static function parseHTMLFragment($aInput, Element $aContextElement)
    {
        // Create a new Document node, and mark it as being an HTML document.
        $doc = new HTMLDocument();
        $mode = $aContextElement->getNodeDocument()->getMode();

        // If the node document of the context element is in quirks mode, then
        // let the Document be in quirks mode. Otherwise, the node document of
        // the context element is in limited-quirks mode, then let the Document
        // be in limited-quirks mode. Otherwise, leave the Document in no-quirks
        // mode.
        $doc->setMode($aContextElement->getNodeDocument()->getMode());

        // Create a new HTML parser, and associate it with the just created
        // Document node.
        $parser = new HTMLParser($doc, true, $aContextElement);
        $localName = $aContextElement->localName;

        // Set the state of the HTML parser's tokenization stage as follows,
        // switching on the context element:
        switch ($localName) {
            case 'title':
            case 'textarea':
                $parser->state->tokenizerState = TokenizerState::RCDATA;

                break;

            case 'style':
            case 'xmp':
            case 'iframe':
            case 'noembed':
            case 'noframes':
                $parser->state->tokenizerState = TokenizerState::RAWTEXT;

                break;

            case 'script':
                $parser->state->tokenizerState = TokenizerState::SCRIPT_DATA;

                break;

            case 'noscript':
                if ($parser->isScriptingEnabled) {
                    $parser->tokenizerState = TokenizerState::RAWTEXT;
                }

                break;

            case 'plaintext':
                $parser->state->tokenizerState = TokenizerState::PLAINTEXT;
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
        $parser->openElements->push($root);

        // If the context element is a template element, push "in template" onto
        // the stack of template insertion modes so that it is the new current
        // template insertion mode.
        if ($aContextElement instanceof HTMLTemplateElement) {
            $parser->templateInsertionModes->push(
                ParserInsertionMode::IN_TEMPLATE
            );
        }

        // Create a start tag token whose name is the local name of context and
        // whose attributes are the attributes of context.
        $token = new StartTagToken($localName);
        $attributes = $token->attributes;

        foreach ($aContextElement->attributes as $attr) {
            $attrToken = new AttributeToken($attr->name, $attr->value);
            $attributes->push($attrToken);
        }

        // Let this start tag token be the start tag token of the context node,
        // e.g. for the purposes of determining if it is an HTML integration
        // point.
        $parser->tokenRepository->attach(
            $aContextElement,
            $token
        );

        // Reset the parser's insertion mode appropriately.
        $parser->resetInsertionMode();
        $node = $aContextElement;

        // Set the parser's form element pointer to the nearest node to the
        // context element that is a form element (going straight up the
        // ancestor chain, and including the element itself, if it is a form
        // element), if any. (If there is no such form element, the form element
        // pointer keeps its initial value, null.)
        while ($node) {
            if ($node instanceof HTMLFormElement) {
                $parser->state->formElementPointer = $node;
                break;
            }

            $node = $node->parentNode;
        }

        // Place the input into the input stream for the HTML parser just
        // created. The encoding confidence is irrelevant.
        $parser->preprocessInputStream($aInput);

        // Start the parser and let it run until it has consumed all the
        // characters just inserted into the input stream.
        $parser->run();

        // Return the child nodes of root, in tree order.
        return $root->childNodes;
    }

    public function run()
    {
        $gen = $this->tokenizer->run();

        foreach ($gen as $token) {
            if ($token instanceof StartTagToken) {
                $this->tokenizer->setLastEmittedStartTagToken($token);
            } elseif ($token instanceof EndTagToken) {
                // When an end tag token is emitted with attributes, that is a
                // parse error.
                if (!$token->attributes->isEmpty()) {
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
            if ($token instanceof StartTagToken &&
                !$token->wasAcknowledged()
            ) {
                // Parse error.
            }
        }

        $this->textBuilder->flushText();
    }

    public function preprocessInputStream($aInput)
    {
        $aInput = mb_convert_encoding($aInput, 'UTF-8');

        if (preg_match(
            '/[\x01-\x08\x0E-\x1F\x7F-\x9F\x{FDD0}-\x{FDEF}\x0B' .
            '\x{FFFE}\x{FFFF}' .
            '\x{1FFFE}\x{1FFFF}' .
            '\x{2FFFE}\x{2FFFF}' .
            '\x{3FFFE}\x{3FFFF}' .
            '\x{4FFFE}\x{4FFFF}' .
            '\x{5FFFE}\x{5FFFF}' .
            '\x{6FFFE}\x{6FFFF}' .
            '\x{7FFFE}\x{7FFFF}' .
            '\x{8FFFE}\x{8FFFF}' .
            '\x{9FFFE}\x{9FFFF}' .
            '\x{AFFFE}\x{AFFFF}' .
            '\x{BFFFE}\x{BFFFF}' .
            '\x{CFFFE}\x{CFFFF}' .
            '\x{DFFFE}\x{DFFFF}' .
            '\x{EFFFE}\x{EFFFF}' .
            '\x{FFFFE}\x{FFFFF}' .
            '\x{10FFFE}\x{10FFFF}]/u',
            $aInput
        )) {
            // Parse error
        }

        // Any character that is a not a Unicode character, i.e. any isolated
        // surrogate, is a parse error. (These can only find their way into the
        // input stream via script APIs such as document.write().)
        if (!mb_check_encoding($aInput, 'UTF-8')) {
            // Parse error
        }

        // U+000D CARRIAGE RETURN (CR) characters and U+000A LINE FEED (LF)
        // characters are treated specially. Any LF character that immediately
        // follows a CR character must be ignored, and all CR characters must
        // then be converted to LF characters. Thus, newlines in HTML DOMs are
        // represented by LF characters, and there are never any CR characters
        // in the input to the tokenization stage.
        $this->inputStream->append(
            preg_replace(['/\x0D\x0A/u', '/\x0D/u'], "\x0A", $aInput)
        );
    }
}
