<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentReadyState;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Element\HTML\Support\FormAssociable;
use Rowbot\DOM\Element\HTML\Support\Resettable;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\HTML\TokenizerState;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Parser\Token\Token;
use Rowbot\DOM\Text;

use function is_string;
use function preg_match;

abstract class InsertionMode
{
    protected const RAW_TEXT_ELEMENT_ALGORITHM = 1;
    protected const RCDATA_ELEMENT_ALGORITHM   = 2;

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

    abstract public function processToken(TreeBuilderContext $context, Token $token): void;

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adjust-foreign-attributes
     */
    protected function adjustForeignAttributes(TagToken $token): void
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
    protected function adjustMathMLAttributes(TagToken $token): void
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
    protected function adjustSVGAttributes(TagToken $token): void
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
    protected function createElementForToken(
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
    protected function generateImpliedEndTags(TreeBuilderContext $context, string $excluded = ''): void
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

        foreach ($context->parser->openElements as $currentNode) {
            if (!isset($tags[$currentNode->localName])) {
                break;
            }

            $context->parser->openElements->pop();
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
    protected function getAppropriatePlaceForInsertingNode(TreeBuilderContext $context, Node $overrideTarget = null): array
    {
        // If there was an override target specified, then let target be the
        // override target. Otherwise, let target be the current node.
        $target = $overrideTarget ?? $context->parser->openElements->bottom();

        // NOTE: Foster parenting happens when content is misnested in tables.
        if (
            $context->fosterParenting
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

            foreach ($context->parser->openElements as $key => $element) {
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
                    $adjustedInsertionLocation = [$context->parser->openElements->top(), 'beforeend'];

                    break;
                }

                if ($lastTable->parentNode) {
                    $adjustedInsertionLocation = [$lastTable, 'beforebegin'];

                    break;
                }

                $previousElement = $context->parser->openElements->itemAt($lastTableIndex - 1);
                $adjustedInsertionLocation = [$previousElement, 'beforeend'];
            } while (false);
        } else {
            $adjustedInsertionLocation = [$target, 'beforeend'];
        }

        if (
            $adjustedInsertionLocation[0] instanceof HTMLTemplateElement
            && $adjustedInsertionLocation[1] === 'beforeend'
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
    protected function insertCharacter(TreeBuilderContext $context, $data): void
    {
        // Let data be the characters passed to the algorithm, or, if no
        // characters were explicitly specified, the character of the character
        // token being processed.
        $data = is_string($data) ? $data : $data->data;

        // Let the adjusted insertion location be the appropriate place for
        // inserting a node.
        $adjustedInsertionLocation = $this->getAppropriatePlaceForInsertingNode($context);

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
    protected function insertComment(TreeBuilderContext $context, CommentToken $token, array $position = null): void
    {
        // Let data be the data given in the comment token being processed.
        $data = $token->data;

        // If position was specified, then let the adjusted insertion location
        // be position. Otherwise, let adjusted insertion location be the
        // appropriate place for inserting a node.
        $adjustedInsertionLocation = $position ?? $this->getAppropriatePlaceForInsertingNode($context);

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
    protected function insertForeignElement(TreeBuilderContext $context, TagToken $token, ?string $namespace): Element
    {
        // Let the adjusted insertion location be the appropriate place for
        // inserting a node.
        $adjustedInsertionLocation = $this->getAppropriatePlaceForInsertingNode($context);

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
        $context->parser->openElements->push($element);
        $context->elementTokenMap->attach($element, $token);

        // Return the newly created element.
        return $element;
    }

    /**
     * Inserts a node based at a specific location. It follows similar rules to
     * Element's insertAdjacentHTML method.
     *
     * @param array{0: \Rowbot\DOM\Node, 1: 'beforeend'|'beforebegin'|'afterend'|'afterbegin'} $position
     */
    protected function insertNode(Node $node, array $position): void
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

    protected function isHTMLElementWithName(Node $node, string $localName): bool
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
    protected function parseGenericTextElement(TreeBuilderContext $context, StartTagToken $token, int $algorithm): void
    {
        // Insert an HTML element for the token.
        $this->insertForeignElement($context, $token, Namespaces::HTML);

        // If the algorithm that was invoked is the generic raw text element
        // parsing algorithm, switch the tokenizer to the RAWTEXT state;
        // otherwise the algorithm invoked was the generic RCDATA element
        // parsing algorithm, switch the tokenizer to the RCDATA state.
        if ($algorithm === self::RAW_TEXT_ELEMENT_ALGORITHM) {
            $context->parser->tokenizerState = TokenizerState::RAWTEXT;
        } else {
            $context->parser->tokenizerState = TokenizerState::RCDATA;
        }

        // Let the original insertion mode be the current insertion mode.
        $context->originalInsertionMode = $context->insertionMode;

        // Then, switch the insertion mode to "text".
        $context->insertionMode = new TextInsertionMode();
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#stop-parsing
     */
    protected function stopParsing(TreeBuilderContext $context): void
    {
        // TODO: Set the current document readiness to "interactive" and the
        // insertion point to undefined.
        $context->document->setReadyState(DocumentReadyState::INTERACTIVE);

        // Pop all the nodes off the stack of open elements.
        $context->parser->openElements->clear();

        // TODO: Lots of stuff
    }
}
