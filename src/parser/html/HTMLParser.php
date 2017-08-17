<?php
use phpjs\parser\OpenElementStack;
namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentMode;
use Rowbot\DOM\DocumentReadyState;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLFormElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLTableCaptionElement;
use Rowbot\DOM\Element\HTML\HTMLTableCellElement;
use Rowbot\DOM\Element\HTML\HTMLTableColElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Element\HTML\HTMLSelectElement;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack;
use Rowbot\DOM\Parser\Parser;
use Rowbot\DOM\Parser\Token\AttributeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;

class HTMLParser extends Parser
{
    // Flags
    const SCRIPTING_ENABLED = 1;
    const PAUSED = 2;

    private $activeFormattingElements;
    private $document;
    private $encodingConfidence;
    private $flags;
    private $formElementPointer;
    private $headElementPointer;
    private $fragmentCase;
    private $insertionMode;
    private $openElements;
    private $originalInsertionMode;
    private $templateInsertionModes;
    private $tokenizerState;
    private $treeBuilder;

    public function __construct(Document $aDocument, $aFragmentCase = false)
    {
        parent::__construct();

        $this->activeFormattingElements = new ActiveFormattingElementStack();
        $this->document = $aDocument;
        $this->encodingConfidence = null;
        $this->flags = 0;
        $this->formElementPointer = null;
        $this->headElementPointer = null;
        $this->fragmentCase = $aFragmentCase;
        $this->insertionMode = ParserInsertionMode::INITIAL;
        $this->tokenizerState = TokenizerState::DATA;
        $this->openElements = new OpenElementStack();
        $this->originalInsertionMode = null;
        $this->templateInsertionModes = new \SplDoublyLinkedList();
        $this->treeBuilder = new TreeBuilder($aDocument, $this);
        $this->tokenizer = new Tokenizer($this->inputStream, $this);
    }

    /**
     * @see https://html.spec.whatwg.org/#adjusted-current-node
     *
     * @return Element
     */
    public function getAdjustedCurrentNode()
    {
        if ($this->fragmentCase && $this->openElements->count() == 1) {
            return $this->mContextElement;
        }

        return $this->openElements->top();
    }

    /**
     * Returns whether of not the parser was created as part of the fragment
     * parsing algorithm for HTML nodes.
     *
     * @return bool
     */
    public function isFragmentCase()
    {
        return $this->fragmentCase;
    }

    /**
     * Resets the HTML Parser's insertion mode.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#reset-the-insertion-mode-appropriately
     */
    public function resetInsertionMode()
    {
        $last = false;
        $node = $this->openElements->top();
        $this->openElements->rewind();

        while (true) {
            if ($this->openElements[0] === $node) {
                $last = true;

                if ($this->fragmentCase) {
                    // Fragment case
                    $node = $this->mContextElement;
                }
            }

            if ($node instanceof HTMLSelectElement) {
                if (!$last) {
                    $ancestor = $node;

                    while ($this->openElements->valid()) {
                        if ($ancestor === $this->openElements[0]) {
                            break;
                        }

                        $this->openElements->next();
                        $ancestor = $this->openElements->current();

                        if ($ancestor instanceof HTMLTemplateElement) {
                            break;
                        }

                        if ($ancestor instanceof HTMLTableElement) {
                            $this->insertionMode =
                                ParserInsertionMode::IN_SELECT_IN_TABLE;
                            break 2;
                        }
                    }
                }

                $this->insertionMode = ParserInsertionMode::IN_SELECT;
                break;
            } elseif ($node instanceof HTMLTableCellElement && !$last) {
                $this->insertionMode = ParserInsertionMode::IN_CELL;
                break;
            } elseif ($node instanceof HTMLTableRowElement) {
                $this->insertionMode = ParserInsertionMode::IN_ROW;
                break;
            } elseif ($node instanceof HTMLTableSectionElement) {
                $this->insertionMode = ParserInsertionMode::IN_TABLE_BODY;
                break;
            } elseif ($node instanceof HTMLTableCaptionElement) {
                $this->insertionMode = ParserInsertionMode::IN_CAPTION;
                break;
            } elseif ($node instanceof HTMLTableColElement &&
                $node->localName === 'colgroup'
            ) {
                $this->insertionMode = ParserInsertionMode::IN_COLUMN_GROUP;
                break;
            } elseif ($node instanceof HTMLTableElement) {
                $this->insertionMode = ParserInsertionMode::IN_TABLE;
                break;
            } elseif ($node instanceof HTMLTemplateElement) {
                $this->insertionMode = $this->templateInsertionModes->top();
                break;
            } elseif ($node instanceof HTMLHeadElement && !$last) {
                $this->insertionMode = ParserInsertionMode::IN_HEAD;
                break;
            } elseif ($node instanceof HTMLBodyElement) {
                $this->insertionMode = ParserInsertionMode::IN_BODY;
                break;
            } elseif ($node instanceof HTMLFrameSetElement) {
                // Fragment case
                $this->insertionMode = ParserInsertionMode::IN_FRAMESET;
                break;
            } elseif ($node instanceof HTMLHtmlElement) {
                if (!$this->headElementPointer) {
                    // Fragment case
                    $this->insertionMode = ParserInsertionMode::BEFORE_HEAD;
                } else {
                    $this->insertionMode = ParserInsertionMode::AFTER_HEAD;
                }

                break;
            } elseif ($last) {
                // Fragment case
                $this->insertionMode = ParserInsertionMode::IN_BODY;
            }

            $this->openElements->next();

            if (!$this->openElements->valid()) {
                return;
            }

            $node = $this->openElements->current();
        }
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
        $parser = new HTMLParser($doc, true);
        $parser->mContextElement = $aContextElement;
        $localName = $aContextElement->localName;

        // Set the state of the HTML parser's tokenization stage as follows,
        // switching on the context element:
        switch ($localName) {
            case 'title':
            case 'textarea':
                $parser->tokenizerState = TokenizerState::RCDATA;

                break;

            case 'style':
            case 'xmp':
            case 'iframe':
            case 'noembed':
            case 'noframes':
                $parser->tokenizerState = TokenizerState::RAWTEXT;

                break;

            case 'script':
                $parser->tokenizerState = TokenizerState::SCRIPT_DATA;

                break;

            case 'noscript':
                if ($parser->flags & self::SCRIPTING_ENABLED) {
                    $parser->tokenizerState = TokenizerState::RAWTEXT;
                }

                break;

            case 'plaintext':
                $parser->tokenizerState = TokenizerState::PLAINTEXT;
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
        $parser->treeBuilder->getTokenRepository()->attach(
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
                $parser->formElementPointer = $node;
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

    public function getTokenizer()
    {
        return $this->tokenizer;
    }

    public function getTreeBuilder()
    {
        return $this->treeBuilder;
    }

    public function getOpenElementStack()
    {
        return $this->openElements;
    }

    public function getActiveFormattingElementStack()
    {
        return $this->activeFormattingElements;
    }

    public function getTemplateInsertionModeStack()
    {
        return $this->templateInsertionModes;
    }

    public function getInsertionMode()
    {
        return $this->insertionMode;
    }

    public function setInsertionMode($aMode)
    {
        $this->insertionMode = $aMode;
    }

    public function getOriginalInsertionMode()
    {
        return $this->originalInsertionMode;
    }

    public function setOriginalInsertionMode($aMode)
    {
        $this->originalInsertionMode = $aMode;
    }

    public function getTokenizerState()
    {
        return $this->tokenizerState;
    }

    public function setTokenizerState($aState)
    {
        $this->tokenizerState = $aState;
    }

    public function getFormElementPointer()
    {
        return $this->formElementPointer;
    }

    public function setFormElementPointer(HTMLFormElement $aElement = null)
    {
        $this->formElementPointer = $aElement;
    }

    public function getHeadElementPointer()
    {
        return $this->headElementPointer;
    }

    public function setHeadElementPointer(HTMLHeadElement $aElement = null)
    {
        $this->headElementPointer = $aElement;
    }

    public function getEncodingConfidence()
    {
        return $this->encodingConfidence;
    }

    public function isScriptingEnabled()
    {
        return (bool) ($this->flags & self::SCRIPTING_ENABLED);
    }

    public function isPaused()
    {
        return (bool) ($this->flags & self::PAUSED);
    }

    public function run()
    {
        $gen = $this->tokenizer->run();

        foreach ($gen as $token) {
            if ($token === null) {
                continue;
            }

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
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#serialising-html-fragments
     * @param  Element|Document|DocumentFragment   $aNode [description]
     * @return string
     */
    public static function serializeHTMLFragment(Node $aNode)
    {
        $s = '';

        // If the node is a template element, then let the node instead be the
        // template element's template contents (a DocumentFragment node).
        if ($aNode instanceof HTMLTemplateElement) {
            $aNode = $aNode->content;
        }

        foreach ($aNode->childNodes as $currentNode) {
            switch ($currentNode->nodeType) {
                case Node::ELEMENT_NODE:
                    switch ($currentNode->namespaceURI) {
                        case Namespaces::HTML:
                        case Namespaces::MATHML:
                        case Namespaces::SVG:
                            $tagname = $currentNode->localName;

                            break;

                        default:
                            $tagname = $currentNode->tagName;
                    }

                    $s .= '<' . $tagname;

                    foreach ($currentNode->getAttributeList() as $attr) {
                        $s .= ' ' . self::serializeContentAttributeName($attr);
                        $s .= '="' . self::escapeHTMLString(
                            $attr->value,
                            true
                        ) . '"';
                    }

                    $s .= '>';
                    $localName = $currentNode->localName;

                    // If the current node's local name is a known void element,
                    // then move on to current node's next sibling, if any.
                    if (preg_match(self::VOID_TAGS, $localName)) {
                        continue 2;
                    }

                    $s .= self::serializeHTMLFragment($currentNode);
                    $s .= '</' . $tagname . '>';

                    break;

                case Node::TEXT_NODE:
                    $localName = $currentNode->parentNode->localName;

                    if ($localName === 'style' ||
                        $localName === 'script' ||
                        $localName === 'xmp' ||
                        $localName === 'iframe' ||
                        $localName === 'noembed' ||
                        $localName === 'noframes' ||
                        $localName === 'plaintext' ||
                        $localName === 'noscript'
                    ) {
                        $s .= $currentNode->data;
                    } else {
                        $s .= self::escapeHTMLString($currentNode->data);
                    }

                    break;

                case Node::COMMENT_NODE:
                    $s .= '<!--' . $currentNode->data . '-->';

                    break;

                case Node::PROCESSING_INSTRUCTION_NODE:
                    $s .= '<?' . $currentNode->target . ' ' .
                        $currentNode->data . '>';

                    break;

                case Node::DOCUMENT_TYPE_NODE:
                    $s .= '<!DOCTYPE ' . $currentNode->name . '>';

                    break;
            }
        }

        return $s;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#escapingString
     *
     * @param string $aString The input string to be escaped.
     *
     * @return string The escaped input string.
     */
    public static function escapeHTMLString($aString, $aInAttributeMode = false)
    {
        if ($aString === '') {
            return '';
        }

        $search = ['&', "\xC2\xA0"];
        $replace = ['&amp;', '&nbsp;'];

        if ($aInAttributeMode) {
            $search[] = '"';
            $replace[] = '&quot;';
        } else {
            $search += ['<', '>'];
            $replace += ['&lt;', '&gt;'];
        }

        return str_replace($search, $replace, $aString);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#stop-parsing
     */
    public function stopParsing()
    {
        // TODO: Set the current document readiness to "interactive" and the
        // insertion point to undefined.
        $this->document->setReadyState(DocumentReadyState::INTERACTIVE);

        // Pop all the nodes off the stack of open elements.
        $this->openElements->clear();

        // TODO: Lots of stuff
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#attribute's-serialised-name
     *
     * @param Attr $aAttr The attribute whose name is to be serialized.
     *
     * @return string The attribute's serialized name.
     */
    protected static function serializeContentAttributeName(Attr $aAttr)
    {
        switch ($aAttr->namespaceURI) {
            case null:
                $serializedName = $aAttr->localName;

                break;

            case Namespaces::XML:
                $serializedName = 'xml:' . $aAttr->localName;

                break;

            case Namespaces::XMLNS:
                $localName = $aAttr->localName;

                if ($localName === 'xmlns') {
                    $serializedName = 'xmlns';
                } else {
                    $serializedName = 'xmlns:' . $localName;
                }

                break;

            case Namespaces::XLINK:
                $serializedName = 'xlink:' . $aAttr->localName;

                break;

            default:
                $serializedName = $aAttr->name;
        }

        return $serializedName;
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

    /*public function dispatcher()
    {
        $inForignContent = true;

        if ($this->openElements->isEmpty()) {
            $inForignContent = false;
        }

        $adjustedCurrentNode = $this->getAdjustedCurrentNode();

        if ($inForignContent &&
            $adjustedCurrentNode->namespaceURI === Namespaces::HTML
        ) {
            $inForignContent = false;
        }

        if ($inForignContent &&
            $this->treeBuilder->isMathMLTextIntegrationPoint(
                $adjustedCurrentNode
            )
            $token instanceof StartTagToken &&
            $token->tagName !== 'mglyph' &&
            $token->tagName !== 'malignmark'
        ) {
            $inForignContent = false;
        }

        if ($inForignContent &&
            $this->treeBuilder->isMathMLTextIntegrationPoint(
                $adjustedCurrentNode
            ) &&
            $token instanceof CharacterToken
        ) {
            $inForignContent = false;
        }

        if ($inForignContent &&
            $adjustedCurrentNode->namespaceURI === Namespaces::MATHML &&
            $adjustedCurrentNode->tagName === 'annotation-xml' &&
            $token instanceof StartTagToken &&
            $token->tagName === 'svg'
        ) {
            $inForignContent = false;
        }

        if ($inForignContent &&
            $this->treeBuilder->isHTMLIntegrationPoint($adjustedCurrentNode) &&
            ($token instanceof StartTagToken ||
                $token instanceof CharacterToken)
        ) {
            $inForignContent = false;
        }

        if ($inForignContent && $token instanceof EOFToken) {
            $inForignContent = false;
        }

        if ($inForignContent) {

        }
    }*/

    public function sniffEncoding()
    {
    }
}
