<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentReadyState;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLSelectElement;
use Rowbot\DOM\Element\HTML\HTMLTableCaptionElement;
use Rowbot\DOM\Element\HTML\HTMLTableCellElement;
use Rowbot\DOM\Element\HTML\HTMLTableColElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Element\HTML\Support\FormAssociable;
use Rowbot\DOM\Element\HTML\Support\Resettable;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack;
use Rowbot\DOM\Parser\HTML\InsertionMode\AfterHeadInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\BeforeHeadInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InBodyInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InCaptionInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InCellInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InColumnGroupInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InFrameSetInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InHeadInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InitialInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InRowInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InSelectInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InSelectInTableInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InTableBodyInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InTableInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\TextInsertionMode;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Text;
use SplObjectStorage;
use SplStack;

use function is_string;
use function preg_match;

final class TreeBuilderContext
{
    public const RAW_TEXT_ELEMENT_ALGORITHM = 1;
    public const RCDATA_ELEMENT_ALGORITHM   = 2;

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#adjust-svg-attributes
     */
    private const SVG_ATTRIBUTES = [
        'attributename'       => 'attributeName',
        'attributetype'       => 'attributeType',
        'basefrequency'       => 'baseFrequency',
        'baseprofile'         => 'baseProfile',
        'calcmode'            => 'calcMode',
        'clippathunits'       => 'clipPathUnits',
        'diffuseconstant'     => 'diffuseConstant',
        'edgemode'            => 'edgeMode',
        'filterunits'         => 'filterUnits',
        'glyphref'            => 'glyphRef',
        'gradienttransform'   => 'gradientTransform',
        'gradientunits'       => 'gradientUnits',
        'kernelmatrix'        => 'kernelMatrix',
        'kernelunitlength'    => 'kernelUnitLength',
        'keypoints'           => 'keyPoints',
        'keysplines'          => 'keySplines',
        'keytimes'            => 'keyTimes',
        'lengthadjust'        => 'lengthAdjust',
        'limitingconeangle'   => 'limitingConeAngle',
        'markerheight'        => 'markerHeight',
        'markerunits'         => 'markerUnits',
        'markerwidth'         => 'markerWidth',
        'maskcontentunits'    => 'maskContentUnits',
        'maskunits'           => 'maskUnits',
        'numoctaves'          => 'numOctaves',
        'pathlength'          => 'pathLength',
        'patterncontentunits' => 'patternContentUnits',
        'patterntransform'    => 'patternTransform',
        'patternunits'        => 'patternUnits',
        'pointsatx'           => 'pointsAtX',
        'pointsaty'           => 'pointsAtY',
        'pointsatz'           => 'pointsAtZ',
        'preservealpha'       => 'preserveAlpha',
        'preserveaspectratio' => 'preserveAspectRatio',
        'primitiveunits'      => 'primitiveUnits',
        'refx'                => 'refX',
        'refy'                => 'refY',
        'repeatcount'         => 'repeatCount',
        'repeatdur'           => 'repeatDur',
        'requiredextensions'  => 'requiredExtensions',
        'requiredfeatures'    => 'requiredFeatures',
        'specularconstant'    => 'specularConstant',
        'specularexponent'    => 'specularExponent',
        'spreadmethod'        => 'spreadMethod',
        'startoffset'         => 'startOffset',
        'stddeviation'        => 'stdDeviation',
        'stitchtiles'         => 'stitchTiles',
        'surfacescale'        => 'surfaceScale',
        'systemlanguage'      => 'systemLanguage',
        'tablevalues'         => 'tableValues',
        'targetx'             => 'targetX',
        'targety'             => 'targetY',
        'textlength'          => 'textLength',
        'viewbox'             => 'viewBox',
        'viewtarget'          => 'viewTarget',
        'xchannelselector'    => 'xChannelSelector',
        'ychannelselector'    => 'yChannelSelector',
        'zoomandpan'          => 'zoomAndPan',
    ];

    /**
     * The stack of active formatting elements.
     *
     * @var \Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack
     */
    public $activeFormattingElements;

    /**
     * The document the parser is associated with.
     *
     * @var \Rowbot\DOM\Document
     */
    public $document;

    /**
     * Stores the insertion mode used by the Treebuilder.
     *
     * @var \Rowbot\DOM\Parser\HTML\InsertionMode\InsertionMode
     */
    public $insertionMode;

    /**
     * The stack of template insertion modes.
     *
     * @var \SplStack<class-string<\Rowbot\DOM\Parser\HTML\InsertionMode\InsertionMode>>
     */
    public $templateInsertionModes;

    /**
     * A collection of nodes and the tokens that were used to create them.
     *
     * @var \SplObjectStorage<\Rowbot\DOM\Node, \Rowbot\DOM\Parser\Token\Token>
     */
    public $tokenRepository;

    /**
     * Whether or not foster-parenting mode is active.
     *
     * @var bool
     */
    public $fosterParenting;

    /**
     * @var string
     */
    public $framesetOk;

    /**
     * Stores the insertion mode that the TreeBuilder should return to after
     * it is done processing the current token in the current insertion mode.
     *
     * @var \Rowbot\DOM\Parser\HTML\InsertionMode\InsertionMode
     */
    public $originalInsertionMode;

    /**
     * @var \Rowbot\DOM\Parser\HTML\ParserContext
     */
    public $parser;

    /**
     * A list of character tokens pending insertion during table building.
     *
     * @var list<\Rowbot\DOM\Parser\Token\CharacterToken>
     */
    public $pendingTableCharacterTokens;

    /**
     * @param \SplObjectStorage<\Rowbot\DOM\Node, \Rowbot\DOM\Parser\Token\Token> $tokenRepository
     */
    public function __construct(
        Document $document,
        ParserContext $parser,
        ActiveFormattingElementStack $activeFormattingElementStack,
        SplObjectStorage $tokenRepository
    ) {
        $this->document                    = $document;
        $this->parser                      = $parser;
        $this->activeFormattingElements    = $activeFormattingElementStack;
        $this->tokenRepository             = $tokenRepository;
        $this->insertionMode               = new InitialInsertionMode($this);
        $this->originalInsertionMode       = $this->insertionMode;
        $this->framesetOk                  = 'ok';
        $this->templateInsertionModes      = new SplStack();
        $this->pendingTableCharacterTokens = [];
        $this->fosterParenting             = false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adjust-foreign-attributes
     */
    public function adjustForeignAttributes(TagToken $token): void
    {
        foreach ($token->attributes as $attr) {
            $name = $attr->name;

            if (
                preg_match(
                    '/^(xlink):(actuate|arcrole|href|role|show|title|type)$/',
                    $name,
                    $matches
                )
            ) {
                $attr->prefix = $matches[1];
                $attr->name = $matches[2];
                $attr->namespace = Namespaces::XLINK;
            } elseif (preg_match('/^(xml):(lang|space)$/', $name, $matches)) {
                $attr->prefix = $matches[1];
                $attr->name = $matches[2];
                $attr->namespace = Namespaces::XML;
            } elseif ($name === 'xmlns' || $name === 'xmlns:xlink') {
                if ($name === 'xmlns:xlink') {
                    $attr->prefix = 'xmlns';
                    $attr->name = 'xlink';
                }

                $attr->namespace = Namespaces::XMLNS;
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adjust-mathml-attributes
     */
    public function adjustMathMLAttributes(TagToken $token): void
    {
        foreach ($token->attributes as $attr) {
            if ($attr->name === 'definitionurl') {
                $attr->name = 'definitionURL';

                break;
            }
        }
    }

    /**
     * Fixes the case of SVG attributes that are not all lowercase.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adjust-svg-attributes
     */
    public function adjustSVGAttributes(TagToken $token): void
    {
        foreach ($token->attributes as $attr) {
            $name = $attr->name;

            if (isset(self::SVG_ATTRIBUTES[$name])) {
                $attr->name = self::SVG_ATTRIBUTES[$name];
            }
        }
    }

    /**
     * Takes a token from the HTML parser and creates the appropriate element
     * for it with the correct namespace and attributes.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#create-an-element-for-the-token
     *
     * @param \Rowbot\DOM\Parser\Token\TagToken $token          The token currently being processed.
     * @param string|null                       $namespace      The namespace of the element that is to be created.
     * @param \Rowbot\DOM\Node                  $intendedParent The parent not which the newely created node will be
     *                                                          inserted in to.
     */
    public function createElementForToken(
        TagToken $token,
        ?string $namespace,
        Node $intendedParent
    ): Element {
        $document = $intendedParent->getNodeDocument();
        $localName = $token->tagName;
        $element = ElementFactory::create($document, $localName, $namespace);
        $attributes = $element->getAttributeList();

        // Append each attribute in the given token to element
        foreach ($token->attributes as $attr) {
            $a = new Attr($document, $attr->name, $attr->value, $attr->namespace, $attr->prefix);
            $attributes->append($a);
        }

        // If element has an xmlns attribute in the XMLNS namespace whose value
        // is not exactly the same as the element's namespace, that is a parse
        // error.
        $value = $element->getAttributeNS(Namespaces::XMLNS, 'xmlns');

        if ($value !== null && $value !== $element->namespaceURI) {
            // Parse error.
        }

        // Similarly, if element has an xmlns:xlink attribute in the XMLNS
        // namespace whose value is not the XLink Namespace, that is a parse
        // error.
        $value = $element->getAttributeNS(Namespaces::XMLNS, 'xmlns:xlink');

        if ($value !== null && $value !== Namespaces::XLINK) {
            // Parse error.
        }

        // If element is a resettable element, invoke its reset algorithm.
        // (This initialises the element's value and checkedness based on the
        // element's attributes.)
        if ($element instanceof Resettable) {
            //TODO: $element->reset();
        }

        // TODO: If element is a form-associated element, and the form element
        // pointer is not null, and there is no template element on the stack of
        // open elements, and element is either not listed or doesn't have a
        // form attribute, and the intended parent is in the same tree as the
        // element pointed to by the form element pointer, associate element
        // with the form element pointed to by the form element pointer, and
        // suppress the running of the reset the form owner algorithm when the
        // parser subsequently attempts to insert the element.
        if ($element instanceof FormAssociable) {
            // TODO
        }

        return $element;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generate-implied-end-tags
     */
    public function generateImpliedEndTags(string $excluded = ''): void
    {
        $tags = [
            'dd'       => 0,
            'dt'       => 0,
            'li'       => 0,
            'optgroup' => 0,
            'option'   => 0,
            'p'        => 0,
            'rb'       => 0,
            'rp'       => 0,
            'rt'       => 0,
            'rtc'      => 0,
        ];

        if ($excluded) {
            unset($tags[$excluded]);
        }

        foreach ($this->parser->openElements as $currentNode) {
            if (!isset($tags[$currentNode->localName])) {
                break;
            }

            $this->parser->openElements->pop();
        }
    }

    /**
     * Gets the appropriate place to insert the node.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#appropriate-place-for-inserting-a-node
     *
     * @param \Rowbot\DOM\Node|null $overrideTarget (optional) When given, it overrides the target insertion point for
     *                                              the node. Default value is null.
     *
     * @return array{0: \Rowbot\DOM\Node, 1: 'beforeend'|'beforebegin'|'afterend'|'afterbegin'}
     */
    public function getAppropriatePlaceForInsertingNode(Node $overrideTarget = null): array
    {
        // If there was an override target specified, then let target be the
        // override target. Otherwise, let target be the current node.
        $target = $overrideTarget ?? $this->parser->openElements->bottom();

        // NOTE: Foster parenting happens when content is misnested in tables.
        if (
            $this->fosterParenting
            && (
                $target instanceof HTMLTableElement
                || $target instanceof HTMLTableSectionElement
                || $target instanceof HTMLTableRowElement
            )
        ) {
            $lastTemplate = null;
            $lastTable = null;
            $lastTableIndex = 0;
            $lastTemplateIndex = 0;

            foreach ($this->parser->openElements as $key => $element) {
                if ($element instanceof HTMLTemplateElement && $lastTemplate === null) {
                    $lastTemplate = $element;
                    $lastTemplateIndex = $key;

                    if ($lastTable) {
                        break;
                    }
                } elseif ($element instanceof HTMLTableElement && $lastTable === null) {
                    $lastTable = $element;
                    $lastTableIndex = $key;

                    if ($lastTemplate) {
                        break;
                    }
                }
            }

            do {
                if ($lastTemplate && (!$lastTable || $lastTemplateIndex > $lastTableIndex)) {
                    $adjustedInsertionLocation = [$lastTemplate->content, 'beforeend'];

                    break;
                }

                if ($lastTable === null) {
                    // Fragment case
                    $adjustedInsertionLocation = [$this->parser->openElements->top(), 'beforeend'];

                    break;
                }

                if ($lastTable->parentNode) {
                    $adjustedInsertionLocation = [$lastTable, 'beforebegin'];

                    break;
                }

                $previousElement = $this->parser->openElements->itemAt($lastTableIndex - 1);
                $adjustedInsertionLocation = [$previousElement, 'beforeend'];
            } while (false);
        } else {
            $adjustedInsertionLocation = [$target, 'beforeend'];
        }

        if (
            $adjustedInsertionLocation[0] instanceof HTMLTemplateElement
            && (
                $adjustedInsertionLocation[1] === 'beforeend'
                || $adjustedInsertionLocation[1] === 'afterbegin'
            )
        ) {
            $adjustedInsertionLocation = [$adjustedInsertionLocation[0]->content, 'beforeend'];
        }

        return $adjustedInsertionLocation;
    }

    /**
     * Inserts a sequence of characters in to a preexisting text node or creates
     * a new text node if one does not exist in the expected insertion location.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#insert-a-character
     *
     * @param \Rowbot\DOM\Parser\Token\CharacterToken|string $data A token that contains character data or a literal
     *                                                             string of characters to insert instead of data from
     *                                                             a token.
     */
    public function insertCharacter($data): void
    {
        // Let data be the characters passed to the algorithm, or, if no
        // characters were explicitly specified, the character of the character
        // token being processed.
        $data = is_string($data) ? $data : $data->data;

        // Let the adjusted insertion location be the appropriate place for
        // inserting a node.
        $adjustedInsertionLocation = $this->getAppropriatePlaceForInsertingNode();

        // If the adjusted insertion location is in a Document node, then abort
        // these steps.
        // NOTE: The DOM will not let Document nodes have Text node children, so
        // they are dropped on the floor.
        if ($adjustedInsertionLocation[0] instanceof Document) {
            return;
        }

        // If there is a Text node immediately before the adjusted insertion l
        // ocation, then append data to that Text node's data. Otherwise, create
        // a new Text node whose data is data and whose node document is the
        // same as that of the element in which the adjusted insertion location
        // finds itself, and insert the newly created node at the adjusted
        // insertion location.
        switch ($adjustedInsertionLocation[1]) {
            case 'beforeend':
                $node = $adjustedInsertionLocation[0]->lastChild;

                break;

            case 'afterend':
                $node = $adjustedInsertionLocation[0]->nextSibling;

                break;

            case 'afterbegin':
                $node = $adjustedInsertionLocation[0]->firstChild;

                break;

            case 'beforebegin':
                $node = $adjustedInsertionLocation[0]->previousSibling;
        }

        if ($node instanceof Text) {
            $node->setData($data, true);

            return;
        }

        $node = new Text($adjustedInsertionLocation[0]->getNodeDocument(), $data);
        $this->insertNode($node, $adjustedInsertionLocation);
    }

    /**
     * Inserts a comment node in to the document while processing a comment
     * token.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#insert-a-comment
     *
     * @param \Rowbot\DOM\Parser\Token\CommentToken                                                 $token
     * @param array{0: \Rowbot\DOM\Node, 1: 'beforeend'|'beforebegin'|'afterend'|'afterbegin'}|null $position
     */
    public function insertComment(CommentToken $token, array $position = null): void
    {
        // Let data be the data given in the comment token being processed.
        $data = $token->data;

        // If position was specified, then let the adjusted insertion location
        // be position. Otherwise, let adjusted insertion location be the
        // appropriate place for inserting a node.
        $adjustedInsertionLocation = $position ?? $this->getAppropriatePlaceForInsertingNode();

        // Create a Comment node whose data attribute is set to data and whose
        // node document is the same as that of the node in which the adjusted
        // insertion location finds itself.
        $ownerDocument = $adjustedInsertionLocation[0]->getNodeDocument();
        $node = new Comment($ownerDocument, $data);

        // Insert the newly created node at the adjusted insertion location.
        $this->insertNode($node, $adjustedInsertionLocation);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#insert-a-foreign-element
     *
     * @param \Rowbot\DOM\Parser\Token\TagToken $token     The start or end tag token that will be used to create a new
     *                                                     element.
     * @param string|null                       $namespace The namespace that the created element will reside in.
     *
     * @return \Rowbot\DOM\Element\Element The newly created element.
     */
    public function insertForeignElement(TagToken $token, ?string $namespace): Element
    {
        // Let the adjusted insertion location be the appropriate place for
        // inserting a node.
        $adjustedInsertionLocation = $this->getAppropriatePlaceForInsertingNode();

        // Create an element for the token in the given namespace, with the
        // intended parent being the element in which the adjusted insertion
        // location finds itself.
        $element = $this->createElementForToken(
            $token,
            $namespace,
            $adjustedInsertionLocation[0]
        );

        // If it is possible to insert an element at the adjusted insertion
        // location, then insert the newly created element at the adjusted
        // insertion location.
        try {
            $this->insertNode($element, $adjustedInsertionLocation);
        } catch (DOMException $e) {
            // NOTE: If the adjusted insertion location cannot accept more
            // elements, e.g. because it's a Document that already has an
            // element child, then the newly created element is dropped on the
            // floor.
        }

        // Push the element onto the stack of open elements so that it is the
        // new current node.
        $this->parser->openElements->push($element);
        $this->tokenRepository->attach($element, $token);

        // Return the newly created element.
        return $element;
    }

    /**
     * Inserts a node based at a specific location. It follows similar rules to
     * Element's insertAdjacentHTML method.
     *
     * @param array{0: \Rowbot\DOM\Node, 1: 'beforeend'|'beforebegin'|'afterend'|'afterbegin'} $position
     */
    public function insertNode(Node $node, array $position): void
    {
        [$relativeNode, $location] = $position;

        if ($location === 'beforebegin') {
            $relativeNode->parentNode->insertNode($node, $relativeNode);
        } elseif ($location === 'afterbegin') {
            $relativeNode->insertNode($node, $relativeNode->firstChild);
        } elseif ($location === 'beforeend') {
            $relativeNode->appendChild($node);
        } elseif ($location === 'afterend') {
            $relativeNode->parentNode->insertNode($node, $relativeNode->nextSibling);
        }
    }

    public function isHTMLElementWithName(Node $node, string $localName): bool
    {
        return $node instanceof Element
            && $node->namespaceURI === Namespaces::HTML
            && $node->localName === $localName;
    }

    /**
     * This algorithm is always invoked in response to a start tag token.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generic-raw-text-element-parsing-algorithm
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generic-rcdata-element-parsing-algorithm
     */
    public function parseGenericTextElement(StartTagToken $token, int $algorithm): void
    {
        // Insert an HTML element for the token.
        $this->insertForeignElement($token, Namespaces::HTML);

        // If the algorithm that was invoked is the generic raw text element
        // parsing algorithm, switch the tokenizer to the RAWTEXT state;
        // otherwise the algorithm invoked was the generic RCDATA element
        // parsing algorithm, switch the tokenizer to the RCDATA state.
        if ($algorithm === self::RAW_TEXT_ELEMENT_ALGORITHM) {
            $this->parser->tokenizerState = TokenizerState::RAWTEXT;
        } else {
            $this->parser->tokenizerState = TokenizerState::RCDATA;
        }

        // Let the original insertion mode be the current insertion mode.
        $this->originalInsertionMode = $this->insertionMode;

        // Then, switch the insertion mode to "text".
        $this->insertionMode = new TextInsertionMode($this);
    }

    /**
     * Resets the HTML Parser's insertion mode.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#reset-the-insertion-mode-appropriately
     */
    public function resetInsertionMode(): void
    {
        $last = false;
        $iterator = $this->parser->openElements->getIterator();

        while ($iterator->valid()) {
            $node = $iterator->current();

            if ($this->parser->openElements->top() === $node) {
                $last = true;

                if ($this->parser->isFragmentCase) {
                    // Fragment case
                    $node = $this->parser->contextElement;
                }
            }

            if ($node instanceof HTMLSelectElement) {
                if (!$last) {
                    $ancestor = $node;

                    while ($iterator->valid()) {
                        if ($ancestor === $this->parser->openElements->top()) {
                            break;
                        }

                        $iterator->next();
                        $ancestor = $iterator->current();

                        if ($ancestor instanceof HTMLTemplateElement) {
                            break;
                        }

                        if ($ancestor instanceof HTMLTableElement) {
                            $this->insertionMode = new InSelectInTableInsertionMode($this);

                            return;
                        }
                    }
                }

                $this->insertionMode = new InSelectInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLTableCellElement && !$last) {
                $this->insertionMode = new InCellInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLTableRowElement) {
                $this->insertionMode = new InRowInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLTableSectionElement) {
                $this->insertionMode = new InTableBodyInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLTableCaptionElement) {
                $this->insertionMode = new InCaptionInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLTableColElement && $node->localName === 'colgroup') {
                $this->insertionMode = new InColumnGroupInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLTableElement) {
                $this->insertionMode = new InTableInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLTemplateElement) {
                $mode = $this->templateInsertionModes->top();
                $this->insertionMode = new $mode($this);

                return;
            }

            if ($node instanceof HTMLHeadElement && !$last) {
                $this->insertionMode = new InHeadInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLBodyElement) {
                $this->insertionMode = new InBodyInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLFrameSetElement) {
                // Fragment case
                $this->insertionMode = new InFrameSetInsertionMode($this);

                return;
            }

            if ($node instanceof HTMLHtmlElement) {
                if (!$this->parser->headElementPointer) {
                    // Fragment case
                    $this->insertionMode = new BeforeHeadInsertionMode($this);

                    return;
                }

                $this->insertionMode = new AfterHeadInsertionMode($this);

                return;
            }

            if ($last) {
                // Fragment case
                $this->insertionMode = new InBodyInsertionMode($this);

                return;
            }

            $iterator->next();
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#stop-parsing
     */
    public function stopParsing(): void
    {
        // TODO: Set the current document readiness to "interactive" and the
        // insertion point to undefined.
        $this->document->setReadyState(DocumentReadyState::INTERACTIVE);

        // Pop all the nodes off the stack of open elements.
        $this->parser->openElements->clear();

        // TODO: Lots of stuff
    }
}
