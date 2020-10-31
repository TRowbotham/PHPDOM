<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentMode;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLAnchorElement;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLButtonElement;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLFormElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHeadingElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLLIElement;
use Rowbot\DOM\Element\HTML\HTMLOptGroupElement;
use Rowbot\DOM\Element\HTML\HTMLOptionElement;
use Rowbot\DOM\Element\HTML\HTMLParagraphElement;
use Rowbot\DOM\Element\HTML\HTMLScriptElement;
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
use Rowbot\DOM\Element\SVG\SVGDescElement;
use Rowbot\DOM\Element\SVG\SVGForeignObjectElement;
use Rowbot\DOM\Element\SVG\SVGScriptElement;
use Rowbot\DOM\Element\SVG\SVGTitleElement;
use Rowbot\DOM\Encoding\EncodingUtils;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\Bookmark;
use Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack;
use Rowbot\DOM\Parser\Collection\OpenElementStack;
use Rowbot\DOM\Parser\Marker;
use Rowbot\DOM\Parser\TextBuilder;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Parser\Token\Token;
use Rowbot\DOM\Text;
use Rowbot\DOM\Utils;
use SplObjectStorage;
use SplStack;

use function count;
use function is_string;
use function mb_strpos;
use function preg_match;

class TreeBuilder
{
    use ParserOrTreeBuilder;
    use TokenizerOrTreeBuilder;

    private const RAW_TEXT_ELEMENT_ALGORITHM = 1;
    private const RCDATA_ELEMENT_ALGORITHM   = 2;

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
     * @see https://html.spec.whatwg.org/multipage/parsing.html#parsing-main-inforeign:svg-namespace
     */
    private const SVG_ELEMENTS = [
        'altgraph'            => 'altGraph',
        'altglyphdef'         => 'altGlyphDef',
        'altglyphitem'        => 'altGlyphItem',
        'animatecolor'        => 'animateColor',
        'animatemotion'       => 'animateMotion',
        'animatetransform'    => 'animateTransform',
        'clippath'            => 'clipPath',
        'feblend'             => 'feBlend',
        'fecolormatrix'       => 'feColorMatrix',
        'fecomponenttransfer' => 'feComponentTransfer',
        'fecomposite'         => 'feComposite',
        'feconvolvematrix'    => 'feConvolveMatrix',
        'fediffuselighting'   => 'feDiffuseLighting',
        'fedisplacementmap'   => 'feDisplacementMap',
        'fedistantlight'      => 'feDistantLight',
        'fedropshadow'        => 'feDropShadow',
        'feflood'             => 'feFlood',
        'fefunca'             => 'feFuncA',
        'fefuncb'             => 'feFuncB',
        'fefuncg'             => 'feFuncG',
        'fefuncr'             => 'feFuncR',
        'fegaussianblur'      => 'feGaussianBlur',
        'feimage'             => 'feImage',
        'femerge'             => 'feMerge',
        'femergenode'         => 'feMergeNode',
        'femorphology'        => 'feMorphology',
        'feOffset'            => 'feOffset',
        'fepointlight'        => 'fePointLight',
        'fespecularlighting'  => 'feSpecularLighting',
        'fespotlight'         => 'feSpotLight',
        'fetile'              => 'feTile',
        'feturbulence'        => 'feTurbulence',
        'foreignobject'       => 'foreignObject',
        'lineargradient'      => 'linearGradient',
        'radialgradient'      => 'radialGradient',
        'textpath'            => 'textPath',
    ];

    /**
     * Whether or not foster-parenting mode is active.
     *
     * @var bool
     */
    private $fosterParenting;

    /**
     * @var string
     */
    private $framesetOk;

    /**
     * Stores the insertion mode that the TreeBuilder should return to after
     * it is done processing the current token in the current insertion mode.
     *
     * @var int
     */
    private $originalInsertionMode;

    /**
     * A list of character tokens pending insertion during table building.
     *
     * @var list<\Rowbot\DOM\Parser\Token\CharacterToken>
     */
    private $pendingTableCharacterTokens;

    /**
     * @param \SplStack<int> $templateInsertionModes
     */
    public function __construct(
        Document $document,
        ActiveFormattingElementStack $activeFormattingElements,
        OpenElementStack $openElements,
        SplStack $templateInsertionModes,
        TextBuilder $textBuilder,
        SplObjectStorage $tokenRepository,
        bool $isFragmentCase,
        bool $isScriptingEnabled,
        ?Element $contextElement,
        ParserState $state
    ) {
        $this->activeFormattingElements = $activeFormattingElements;
        $this->contextElement = $contextElement;
        $this->document = $document;
        $this->fosterParenting = false;
        $this->framesetOk = 'ok';
        $this->isFragmentCase = $isFragmentCase;
        $this->isScriptingEnabled = $isScriptingEnabled;
        $this->openElements = $openElements;
        $this->state = $state;
        $this->templateInsertionModes = $templateInsertionModes;
        $this->textBuilder = $textBuilder;
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-initial-insertion-mode
     */
    private function initialInsertionMode(Token $token): void
    {
        if (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Ignore the token.
        } elseif ($token instanceof CommentToken) {
            $this->insertComment($token, [$this->document, 'beforeend']);
        } elseif ($token instanceof DoctypeToken) {
            $publicId = $token->publicIdentifier;
            $systemId = $token->systemIdentifier;
            $name = $token->name;

            if (
                $name !== 'html'
                || $publicId !== null
                || ($systemId !== null && $systemId !== 'about:legacy-compat')
            ) {
                // Parse error
            }

            // Append a DocumentType node to the Document node, with the name
            // attribute set to the name given in the DOCTYPE token, or the
            // empty string if the name was missing; the publicId attribute set
            // to the public identifier given in the DOCTYPE token, or the empty
            // string if the public identifier was missing; the systemId
            // attribute set to the system identifier given in the DOCTYPE
            // token, or the empty string if the system identifier was missing;
            // and the other attributes specific to DocumentType objects set to
            // null and empty lists as appropriate. Associate the DocumentType
            // node with the Document object so that it is returned as the value
            // of the doctype attribute of the Document object.
            $doctype = new DocumentType(
                $this->document,
                $name ?? '',
                $publicId ?? '',
                $systemId ?? ''
            );
            $this->document->appendChild($doctype);

            // If the document is not an iframe srcdoc document...
            if (!$this->document->isIframeSrcdoc()) {
                // and the DOCTYPE token matches one of the conditions in the
                // following list, the set the Document to quirks mode.
                if (
                    $token->getQuirksMode() === 'on'
                    || $name !== 'html'
                    || $publicId === Utils::toASCIILowercase('-//W3O//DTD W3 HTML Strict 3.0//EN//')
                    || $publicId === Utils::toASCIILowercase('-/W3C/DTD HTML 4.0 Transitional/EN')
                    || $publicId === Utils::toASCIILowercase('HTML')
                    || $systemId === Utils::toASCIILowercase(
                        'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd'
                    )
                    || $this->identifierBeginsWith($publicId, [
                        '+//Silmaril//dtd html Pro v0r11 19970101//',
                        '-//AS//DTD HTML 3.0 asWedit + extensions//',
                        '-//AdvaSoft Ltd//DTD HTML 3.0 asWedit + extensions//',
                        '-//IETF//DTD HTML 2.0 Level 1//',
                        '-//IETF//DTD HTML 2.0 Level 2//',
                        '-//IETF//DTD HTML 2.0 Strict Level 1//',
                        '-//IETF//DTD HTML 2.0 Strict Level 2//',
                        '-//IETF//DTD HTML 2.0 Strict//',
                        '-//IETF//DTD HTML 2.0//',
                        '-//IETF//DTD HTML 2.1E//',
                        '-//IETF//DTD HTML 3.0//',
                        '-//IETF//DTD HTML 3.2 Final//',
                        '-//IETF//DTD HTML 3.2//',
                        '-//IETF//DTD HTML 3//',
                        '-//IETF//DTD HTML Level 0//',
                        '-//IETF//DTD HTML Level 1//',
                        '-//IETF//DTD HTML Level 2//',
                        '-//IETF//DTD HTML Level 3//',
                        '-//IETF//DTD HTML Strict Level 0//',
                        '-//IETF//DTD HTML Strict Level 1//',
                        '-//IETF//DTD HTML Strict Level 2//',
                        '-//IETF//DTD HTML Strict Level 3//',
                        '-//IETF//DTD HTML Strict//',
                        '-//IETF//DTD HTML//',
                        '-//Metrius//DTD Metrius Presentational//',
                        '-//Microsoft//DTD Internet Explorer 2.0 HTML Strict//',
                        '-//Microsoft//DTD Internet Explorer 2.0 HTML//',
                        '-//Microsoft//DTD Internet Explorer 2.0 Tables//',
                        '-//Microsoft//DTD Internet Explorer 3.0 HTML Strict//',
                        '-//Microsoft//DTD Internet Explorer 3.0 HTML//',
                        '-//Microsoft//DTD Internet Explorer 3.0 Tables//',
                        '-//Netscape Comm. Corp.//DTD HTML//',
                        '-//Netscape Comm. Corp.//DTD Strict HTML//',
                        '-//O\'Reilly and Associates//DTD HTML 2.0//',
                        '-//O\'Reilly and Associates//DTD HTML Extended 1.0//',
                        '-//O\'Reilly and Associates//DTD HTML Extended Relaxed 1.0//',
                        '-//SQ//DTD HTML 2.0 HoTMetaL + extensions//',
                        '-//SoftQuad Software//DTD HoTMetaL PRO 6.0::19990601::extensions to HTML 4.0//',
                        '-//SoftQuad//DTD HoTMetaL PRO 4.0::19971010::extensions to HTML 4.0//',
                        '-//Spyglass//DTD HTML 2.0 Extended//',
                        '-//Sun Microsystems Corp.//DTD HotJava HTML//',
                        '-//Sun Microsystems Corp.//DTD HotJava Strict HTML//',
                        '-//W3C//DTD HTML 3 1995-03-24//',
                        '-//W3C//DTD HTML 3.2 Draft//',
                        '-//W3C//DTD HTML 3.2 Final//',
                        '-//W3C//DTD HTML 3.2//',
                        '-//W3C//DTD HTML 3.2S Draft//',
                        '-//W3C//DTD HTML 4.0 Frameset//',
                        '-//W3C//DTD HTML 4.0 Transitional//',
                        '-//W3C//DTD HTML Experimental 19960712//',
                        '-//W3C//DTD HTML Experimental 970421//',
                        '-//W3C//DTD W3 HTML//',
                        '-//W3O//DTD W3 HTML 3.0//',
                        '-//WebTechs//DTD Mozilla HTML 2.0//',
                        '-//WebTechs//DTD Mozilla HTML//',
                    ])
                    || ($systemId === null && $this->identifierBeginsWith($publicId, [
                        '-//W3C//DTD HTML 4.01 Frameset//',
                        '-//W3C//DTD HTML 4.01 Transitional//',
                    ]))
                ) {
                    $this->document->setMode(DocumentMode::QUIRKS);

                    // Otherwise, if the DOCTYPE token matches one of the
                    // conditions in the following list, then set the Document
                    // to limited-quirks mode.
                } elseif (
                    $this->identifierBeginsWith($publicId, [
                        '-//W3C//DTD XHTML 1.0 Frameset//',
                        '-//W3C//DTD XHTML 1.0 Transitional//',
                    ])
                    || ($systemId !== null && $this->identifierBeginsWith($publicId, [
                        '-//W3C//DTD HTML 4.01 Frameset//',
                        '-//W3C//DTD HTML 4.01 Transitional//',
                    ]))
                ) {
                    $this->document->setMode(DocumentMode::LIMITED_QUIRKS);
                }
            }

            // The, switch the insertion mode to "before html".
            $this->state->insertionMode = ParserInsertionMode::BEFORE_HTML;
        } else {
            // If the document is not an iframe srcdoc document, then this
            // is a parse error; set the Document to quirks mode.
            if (!$this->document->isIframeSrcdoc()) {
                // Parse error.
                $this->document->setMode(DocumentMode::QUIRKS);
            }

            // In any case, switch the insertion mode to "before html", then
            // reprocess the token.
            $this->state->insertionMode = ParserInsertionMode::BEFORE_HTML;
            $this->run($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-before-html-insertion-mode
     */
    private function beforeHTMLInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof CommentToken) {
            // Insert a comment as the last child of the Document object.
            $this->insertComment($token, [$this->document, 'beforeend']);
        } elseif (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Create an element for the token in the HTML namespace, with the
            // Document as the intended parent. Append it to the Document
            // object. Put this element in the stack of open elements.
            $node = $this->createElementForToken($token, Namespaces::HTML, $this->document);
            $this->document->appendChild($node);
            $this->openElements->push($node);

            // TODO: If the Document is being loaded as part of navigation of a
            // browsing context, run these steps:

            // Switch the insertion mode to "before head".
            $this->state->insertionMode = ParserInsertionMode::BEFORE_HEAD;
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'head'
                || $tagName === 'body'
                || $tagName === 'html'
                || $tagName === 'br'
            )
        ) {
            // Act as described in the "anything else" entry below.
            $this->beforeHTMLInsertionModeAnythingElse($token);
        } elseif ($token instanceof EndTagToken) {
            // Parse error.
            // Ignore the token.
        } else {
            $this->beforeHTMLInsertionModeAnythingElse($token);
        }
    }

    /**
     * The "before html" insertion mode's "anything else" steps.
     */
    private function beforeHTMLInsertionModeAnythingElse(Token $token): void
    {
        // Create an html element whose node document is the Document
        // object. Append it to the Document object. Put this element in
        // the stack of open elements.
        $node = ElementFactory::create($this->document, 'html', Namespaces::HTML);
        $this->document->appendChild($node);
        $this->openElements->push($node);

        // TODO: If the Document is being loaded as part of navigation of a
        // browsing context, then: run the application cache selection
        // algorithm with no manifest, passing it the Document object.

        // Switch the insertion mode to "before head", then reprocess the token.
        $this->state->insertionMode = ParserInsertionMode::BEFORE_HEAD;
        $this->run($token);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-before-head-insertion-mode
     */
    private function beforeHeadInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Ignore the token.
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'head') {
            // Insert an HTML element for the token.
            $node = $this->insertForeignElement($token, Namespaces::HTML);

            // Set the head element pointer to the newly created head element.
            $this->state->headElementPointer = $node;

            // Switch the insertion mode to "in head".
            $this->state->insertionMode = ParserInsertionMode::IN_HEAD;
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'head'
                || $tagName === 'body'
                || $tagName === 'html'
                || $tagName === 'br'
            )
        ) {
            // Act as described in the "anything else" entry below.
            $this->beforeHeadInsertionModeAnythingElse($token);
        } elseif ($token instanceof EndTagToken) {
            // Parse error.
            // Ignore the token.
        } else {
            $this->beforeHeadInsertionModeAnythingElse($token);
        }
    }

    /**
     * The "before head" insertion mode's "anything else" steps.
     */
    private function beforeHeadInsertionModeAnythingElse(Token $token): void
    {
        // Insert an HTML element for a "head" start tag token with no
        // attributes.
        $node = $this->insertForeignElement(new StartTagToken('head'), Namespaces::HTML);

        // Set the head element pointer to the newly created head element.
        $this->state->headElementPointer = $node;

        // Switch the insertion mode to "in head".
        $this->state->insertionMode = ParserInsertionMode::IN_HEAD;

        // Reprocess the current token.
        $this->run($token);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inhead
     */
    private function inHeadInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Insert the character.
            $this->insertCharacter($token);
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'base'
                || $tagName === 'basefont'
                || $tagName === 'bgsound'
                || $tagName === 'link'
            )
        ) {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'meta') {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $node = $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // If the element has a charset attribute, and getting an encoding
            // from its value results in an encoding, and the confidence is
            // currently tentative, then change the encoding to the resulting
            // encoding. Otherwise, if the element has an http-equiv attribute
            // whose value is an ASCII case-insensitive match for the string
            // "Content-Type", and the element has a content attribute, and
            // applying the algorithm for extracting a character encoding
            // from a meta element to that attribute's value returns an
            // encoding, and the confidence is currently tentative, then
            // change the encoding to the extracted encoding.
            $charset = $node->getAttribute('charset');

            if (
                $charset !== null
                && EncodingUtils::getEncoding($charset) !== false
                && $this->state->encodingConfidence === ParserState::CONFIDENCE_TENTATIVE
            ) {
                // TODO: change the encoding to the resulting encoding
            } elseif (
                ($attr = $node->getAttribute('http-equiv')) !== null
                && Utils::toASCIILowercase($attr) === 'content-type'
                && $node->hasAttribute('content')
            ) {
                // TODO
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'title') {
            // Follow the generic RCDATA element parsing algorithm.
            $this->parseGenericTextElement($token, self::RCDATA_ELEMENT_ALGORITHM);
        } elseif (
            $token instanceof StartTagToken
            && (
                ($tagName === 'noscript' && $this->isScriptingEnabled)
                || ($tagName === 'noframes' || $tagName === 'style')
            )
        ) {
            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement($token, self::RAW_TEXT_ELEMENT_ALGORITHM);
        } elseif (
            $token instanceof StartTagToken
            && $tagName === 'noscript'
            && !$this->isScriptingEnabled
        ) {
            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Switch the insertion mode to "in head noscript".
            $this->state->insertionMode = ParserInsertionMode::IN_HEAD_NOSCRIPT;
        } elseif ($token instanceof StartTagToken && $tagName === 'script') {
            // Let the adjusted insertion location be the appropriate place for
            // inserting a node.
            $adjustedInsertionLocation = $this->getAppropriatePlaceForInsertingNode();

            // Create an element for the token in the HTML namespace, with the
            // intended parent being the element in which the adjusted insertion
            // location finds itself.
            $node = $this->createElementForToken(
                $token,
                Namespaces::HTML,
                $adjustedInsertionLocation[0]
            );

            // TODO: Mark the element as being "parser-inserted" and unset the
            // element's "non-blocking" flag.
            //
            // NOTE: This ensures that, if the script is external, any
            // document.write() calls in the script will execute in-line,
            // instead of blowing the document away, as would happen in most
            // other cases. It also prevents the script from executing until
            // the end tag is seen.

            // If the parser was originally created for the HTML fragment
            // parsing algorithm, then mark the script element as "already
            // started". (fragment case)
            if ($this->isFragmentCase) {
                // TODO
            }

            // TODO: If the parser was invoked via the document.write() or
            // document.writeln() methods, then optionally mark the script
            // element as "already started". (For example, the user agent might
            // use this clause to prevent execution of cross-origin scripts
            // inserted via document.write() under slow network conditions, or
            // when the page has already taken a long time to load.)

            // Insert the newly created element at the adjusted insertion
            // location.
            $this->insertNode($node, $adjustedInsertionLocation);

            // Push the element onto the stack of open elements so that it is
            // the new current node.
            $this->openElements->push($node);

            // Switch the tokenizer to the script data state.
            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;

            // Let the original insertion mode be the current insertion mode.
            $this->originalInsertionMode = $this->state->insertionMode;

            // Switch the insertion mode to "text".
            $this->state->insertionMode = ParserInsertionMode::TEXT;
        } elseif ($token instanceof EndTagToken && $tagName === 'head') {
            // Pop the current node (which will be the head element) off the
            // stack of open elements.
            $this->openElements->pop();

            // Switch the insertion mode to "after head".
            $this->state->insertionMode = ParserInsertionMode::AFTER_HEAD;
        } elseif (
            $token instanceof EndTagToken
            && ($tagName === 'body' || $tagName === 'html' || $tagName === 'br')
        ) {
            // Act as described in the "anything else" entry below.
            $this->inHeadInsertionModeAnythingElse($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'template') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Insert a marker at the end of the list of active formatting
            // elements.
            $this->activeFormattingElements->push(new Marker());

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';

            // Switch the insertion mode to "in template".
            $this->state->insertionMode = ParserInsertionMode::IN_TEMPLATE;

            // Push "in template" onto the stack of template insertion modes so
            // that it is the new current template insertion mode.
            $this->templateInsertionModes->push(ParserInsertionMode::IN_TEMPLATE);
        } elseif ($token instanceof EndTagToken && $tagName === 'template') {
            if (!$this->openElements->containsTemplateElement()) {
                // Parse error.
                // Ignore the token.
            } else {
                // Generate all implied end tags thoroughly.
                $this->generateAllImpliedEndTagsThoroughly();

                // If the current node is not a template element, then this is
                // a parse error.
                $currentNode = $this->openElements->bottom();

                if (!$currentNode instanceof HTMLTemplateElement) {
                    // Parse error
                }

                // Pop elements from the stack of open elements until a
                // template element has been popped from the stack.
                while (!$this->openElements->isEmpty()) {
                    $popped = $this->openElements->pop();

                    if ($popped instanceof HTMLTemplateElement) {
                        break;
                    }
                }

                // Clear the list of active formatting elements up to the last
                // marker.
                $this->activeFormattingElements->clearUpToLastMarker();

                // Pop the current template insertion mode off the stack of
                // template insertion modes.
                $this->templateInsertionModes->pop();

                // Reset the insertion mode appropriately.
                $this->resetInsertionMode();
            }
        } elseif (
            ($token instanceof StartTagToken && $tagName === 'head')
            || $token instanceof EndTagToken
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            $this->inHeadInsertionModeAnythingElse($token);
        }
    }

    /**
     * The "in head" insertion mode "anything else" steps.
     */
    private function inHeadInsertionModeAnythingElse(Token $token): void
    {
        // Pop the current node (which will be the head element) off the
        // stack of open elements.
        $this->openElements->pop();

        // Switch the insertion mode to "after head".
        $this->state->insertionMode = ParserInsertionMode::AFTER_HEAD;

        // Reprocess the token.
        $this->run($token);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inheadnoscript
     */
    private function inHeadNoScriptInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof EndTagToken && $tagName === 'noscript') {
            // Pop the current node (which will be a noscript element) from the
            // stack of open elements; the new current node will be a head
            // element.
            $this->openElements->pop();

            // Switch the insertion mode to "in head".
            $this->state->insertionMode = ParserInsertionMode::IN_HEAD;
        } elseif (
            (
                $token instanceof CharacterToken
                && (
                    ($data = $token->data) === "\x09"
                    || $data === "\x0A"
                    || $data === "\x0C"
                    || $data === "\x0D"
                    || $data === "\x20"
                )
            )
            || $token instanceof CommentToken
            || (
                $token instanceof StartTagToken
                && (
                    $tagName === 'basefont'
                    || $tagName === 'bgsound'
                    || $tagName === 'link'
                    || $tagName === 'meta'
                    || $tagName === 'noframes'
                    || $tagName === 'style'
                )
            )
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif ($token instanceof EndTagToken && $tagName === 'br') {
            // Act as described in the "anything else" entry below.
            $this->inHeadNoScriptInsertionModeAnythingElse($token);
        } elseif (
            ($token instanceof StartTagToken && ($tagName === 'head' || $tagName === 'noscript'))
            || $token instanceof EndTagToken
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            $this->inHeadNoScriptInsertionModeAnythingElse($token);
        }
    }

    /**
     * The "in head noscript" insertion mode "anything else" steps.
     */
    private function inHeadNoScriptInsertionModeAnythingElse(Token $token): void
    {
        // Parse error.
        // Pop the current node (which will be a noscript element) from the
        // stack of open elements; the new current node will be a head
        // element.
        $this->openElements->pop();

        // Switch the insertion mode to "in head".
        $this->state->insertionMode = ParserInsertionMode::IN_HEAD;

        // Reprocess the token.
        $this->run($token);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-head-insertion-mode
     */
    private function afterHeadInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Insert the character.
            $this->insertCharacter($token);
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'body') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';

            // Switch the insertion mode to "in body".
            $this->state->insertionMode = ParserInsertionMode::IN_BODY;
        } elseif ($token instanceof StartTagToken && $tagName === 'frameset') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Switch the insertion mode to "in frameset".
            $this->state->insertionMode = ParserInsertionMode::IN_FRAMESET;
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'base'
                || $tagName === 'basefont'
                || $tagName === 'bgsound'
                || $tagName === 'link'
                || $tagName === 'meta'
                || $tagName === 'noframes'
                || $tagName === 'script'
                || $tagName === 'style'
                || $tagName === 'template'
                || $tagName === 'title'
            )
        ) {
            // Parse error
            // Push the node pointed to by the head element pointer onto the
            // stack of open elements.
            $this->openElements->push($this->state->headElementPointer);

            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);

            // Remove the node pointed to by the head element pointer from the
            // stack of open elements. (It might not be the current node at
            // this point.)
            // NOTE: The head element pointer cannot be null at this point.
            $this->openElements->remove($this->state->headElementPointer);
        } elseif ($token instanceof EndTagToken && $tagName === 'template') {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif (
            $token instanceof EndTagToken
            && ($tagName === 'body' || $tagName === 'html' || $tagName === 'br')
        ) {
            // Act as described in the "anything else" entry below.
            $this->afterHeadInsertionModeAnythingElse($token);
        } elseif (
            ($token instanceof StartTagToken && $tagName === 'head')
            || $token instanceof EndTagToken
        ) {
            // Parse error.
            // Ignore the token
        } else {
            $this->afterHeadInsertionModeAnythingElse($token);
        }
    }

    /**
     * The "after head" insertion mode's "anything else" steps.
     */
    private function afterHeadInsertionModeAnythingElse(Token $token): void
    {
        // Insert an HTML element for a "body" start tag token with no
        // attributes.
        $this->insertForeignElement(new StartTagToken('body'), Namespaces::HTML);

        // Switch the insertion mode to "in body".
        $this->state->insertionMode = ParserInsertionMode::IN_BODY;

        // Reprocess the current token
        $this->run($token);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inbody
     */
    private function inBodyInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof CharacterToken && $token->data === "\x00") {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof CharacterToken) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert the token's character.
            $this->insertCharacter($token);

            if (
                ($data = $token->data) !== "\x09"
                && $data !== "\x0A"
                && $data !== "\x0C"
                && $data !== "\x0D"
                && $data !== "\x20"
            ) {
                // Set the frameset-ok flag to "not ok".
                $this->framesetOk = 'not ok';
            }
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Parse error.
            // If there is a template element on the stack of open elements,
            // then ignore the token.
            if ($this->openElements->containsTemplateElement()) {
                return;
            }

            // Otherwise, for each attribute on the token, check to see if the
            // attribute is already present on the top element of the stack of
            // open elements. If it is not, add the attribute and its
            // corresponding value to that element.
            $firstOnStack = $this->openElements[0];

            foreach ($token->attributes as $attr) {
                $name = $attr->name;

                if (!$firstOnStack->hasAttribute($name)) {
                    $firstOnStack->setAttribute($name, $attr->value);
                }
            }
        } elseif (
            (
                $token instanceof StartTagToken
                && (
                    $tagName === 'base'
                    || $tagName === 'basefont'
                    || $tagName === 'bgsound'
                    || $tagName === 'link'
                    || $tagName === 'meta'
                    || $tagName === 'noframes'
                    || $tagName === 'script'
                    || $tagName === 'style'
                    || $tagName === 'template'
                    || $tagName === 'title'
                )
            )
            || ($token instanceof EndTagToken && $tagName === 'template')
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'body') {
            // Parse error.
            // If the second element on the stack of open elements is not a body
            // element, if the stack of open elements has only one node on it,
            // or if there is a template element on the stack of open elements,
            // then ignore the token. (fragment case)
            if (
                !$this->openElements[1] instanceof HTMLBodyElement
                || count($this->openElements) === 1
                || $this->openElements->containsTemplateElement()
            ) {
                // Fragment case
                // Ignore the token.
                return;
            }

            // Otherwise, set the frameset-ok flag to "not ok"; then, for each
            // attribute on the token, check to see if the attribute is already
            // present on the body element (the second element) on the stack of
            // open elements, and if it is not, add the attribute and its
            // corresponding value to that element.
            $this->framesetOk = 'not ok';
            $body = $this->openElements[1];

            foreach ($token->attributes as $attr) {
                $name = $attr->name;

                if (!$body->hasAttribute($name)) {
                    $body->setAttribute($name, $attr->value);
                }
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'frameset') {
            // Parse error.
            // If the stack of open elements has only one node on it, or if the
            // second element on the stack of open elements is not a body
            // element, then ignore the token. (fragment case)
            $count = count($this->openElements);

            if ($count === 1 || !$this->openElements[1] instanceof HTMLBodyElement) {
                // Fragment case
                // Ignore the token
                return;
            }

            // If the frameset-ok flag is set to "not ok", ignore the token.
            if ($this->framesetOk === 'not ok') {
                // Ignore the token.
                return;
            }

            // Remove the second element on the stack of open elements from its
            // parent node, if it has one.
            if (($body = $this->openElements[1]) && ($parent = $body->parentNode)) {
                $parent->removeChild($body);
            }

            // Pop all the nodes from the bottom of the stack of open elements,
            // from the current node up to, but not including, the root html
            // element.
            for ($i = $count - 1; $i > 0; $i--) {
                $this->openElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Switch the insertion mode to "in frameset".
            $this->state->insertionMode = ParserInsertionMode::IN_FRAMESET;
        } elseif ($token instanceof EOFToken) {
            // If the stack of template insertion modes is not empty, then
            // process the token using the rules for the "in template"
            // insertion mode.
            if (!$this->templateInsertionModes->isEmpty()) {
                $this->inTemplateInsertionMode($token);

                return;
            }

            // If there is a node in the stack of open elements that is not
            // either a dd element, a dt element, an li element, an optgroup
            // element, an option element, a p element, an rb element, an rp
            // element, an rt element, an rtc element, a tbody element, a td
            // element, a tfoot element, a th element, a thead element, a tr
            // element, the body element, or the html element, then this is a
            // parse error.
            $pattern = '/^(dd|dt|li|optgroup|option|p|rb|rp|rt|rtc|';
            $pattern .= 'tbody|td|tfoot|th|thead|tr|body|html)$/';

            foreach ($this->openElements as $el) {
                if (!($el instanceof HTMLElement && preg_match($pattern, $el->localName))) {
                    // Parse error.
                    break;
                }
            }

            // Stop parsing.
            $this->stopParsing();
        } elseif ($token instanceof EndTagToken && $tagName === 'body') {
            // If the stack of open elements does not have a body element
            // in scope, this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInScope('body', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, if there is a node in the stack of open elements
            // that is not either a dd element, a dt element, an li element, an
            // optgroup element, an option element, a p element, an rb element,
            // an rp element, an rt element, an rtc element, a tbody element, a
            // td element, a tfoot element, a th element, a thead element, a tr
            // element, the body element, or the html element, then this is a
            // parse error.
            $pattern = '/^(dd|dt|li|optgroup|option|p|rb|rp|rt|';
            $pattern .= 'rtc|tbody|td|tfoot|th|thead|tr|body|html)$/';

            foreach ($this->openElements as $el) {
                if (!($el instanceof HTMLElement && preg_match($pattern, $el->localName))) {
                    // Parse error.
                    break;
                }
            }

            // Switch the insertion mode to "after body".
            $this->state->insertionMode = ParserInsertionMode::AFTER_BODY;
        } elseif ($token instanceof EndTagToken && $tagName === 'html') {
            // If the stack of open elements does not have a body element in
            // scope, this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInScope('body', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, if there is a node in the stack of open elements
            // that is not either a dd element, a dt element, an li element an
            // optgroup element, an option element, a p element, an rb element,
            // an rp element, an rt element, an rtc element, a tbody element, a
            // td element, a tfoot element, a th element, a thead element, a tr
            // element, the body element, or the html element, then this is a
            // parse error.
            $pattern = '/^(dd|dt|li|optgroup|option|p|rb|rp|rt|';
            $pattern .= 'rtc|tbody|td|tfoot|th|thead|tr|body|html)$/';

            foreach ($this->openElements as $el) {
                if (!($el instanceof HTMLElement && preg_match($pattern, $el->localName))) {
                    // Parse error.
                    break;
                }
            }

            // Switch the insertion mode to "after body".
            $this->state->insertionMode = ParserInsertionMode::AFTER_BODY;

            // Reprocess the token.
            $this->run($token);
        } elseif (
            $token instanceof StartTagToken
            && preg_match(
                '/^(address|article|aside|blockquote|center|details|dialog|'
                . 'dir|div|dl|fieldset|figcaption|figure|footer|header|hgroup|'
                . 'main|menu|nav|ol|p|section|summary|ul)$/',
                $tagName
            )
        ) {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'h1'
                || $tagName === 'h2'
                || $tagName === 'h3'
                || $tagName === 'h4'
                || $tagName === 'h5'
                || $tagName === 'h6'
            )
        ) {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement();
            }

            // If the current node is an HTML element whose tag name is one of
            // "h1", "h2", "h3", "h4", "h5", or "h6", then this is a parse
            // error; pop the current node off the stack of open elements.
            if ($this->openElements->bottom() instanceof HTMLHeadingElement) {
                // Parse error.
                $this->openElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif (
            $token instanceof StartTagToken
            && ($tagName === 'pre' || $tagName === 'listing')
        ) {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // TODO: If the next token is a U+000A LINE FEED (LF) character
            // token, then ignore that token and move on to the next one.
            // (Newlines at the start of pre blocks are ignored as an authoring
            // convenience.)

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';
        } elseif ($token instanceof StartTagToken && $tagName === 'form') {
            // If the form element pointer is not null, and there is no
            // template element on the stack of open elements, then this is a
            // parse error; ignore the token.
            if (
                $this->state->formElementPointer
                && !$this->openElements->containsTemplateElement()
            ) {
                // Parse error.
                // Ignore the token.
            } else {
                // If the stack of open elements has a p element in button
                // scope, then close a p element.
                if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                    $this->closePElement();
                }

                // Insert an HTML element for the token, and, if there is no
                // template element on the stack of open elements, set the
                // form element pointer to point to the element created.
                $node = $this->insertForeignElement($token, Namespaces::HTML);

                if (!$this->openElements->containsTemplateElement()) {
                    $this->state->formElementPointer = $node;
                }
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'li') {
            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';

            // Initialise node to be the current node (the bottommost node of
            // the stack).
            // Step "Loop".
            foreach ($this->openElements as $node) {
                if ($node instanceof HTMLLIElement) {
                    // Generate implied end tags, except for li elements.
                    $this->generateImpliedEndTags('li');

                    // If the current node is not an li element, then this is a
                    // parse error.
                    $currentNode = $this->openElements->bottom();

                    if (!$currentNode instanceof HTMLLIElement) {
                        // Parse error.
                    }

                    // Pop elements from the stack of open elements until an li
                    // element has been popped from the stack.
                    while (!$this->openElements->isEmpty()) {
                        if ($this->openElements->pop() instanceof HTMLLIElement) {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                // If node is in the special category, but is not an address,
                // div, or p element, then jump to the step labeled done below.
                // Otherwise, set node to the previous entry in the stack
                // of open elements and return to the step labeled loop.
                if (
                    $this->isSpecialNode($node)
                    && !($node instanceof HTMLElement
                        && (($name = $node->localName) === 'address'
                            || $name === 'div'
                            || $name === 'p'
                        )
                    )
                ) {
                    break;
                }
            }

            // Step "Done".
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement();
            }

            // Finally, insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof StartTagToken && ($tagName === 'dd' || $tagName === 'dt')) {
            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';

            // Initialise node to be the current node (the bottommost node of the stack).
            // Step "Loop".
            foreach ($this->openElements as $node) {
                if ($node instanceof HTMLElement && $node->localName === 'dd') {
                    // Generate implied end tags, except for dd elements.
                    $this->generateImpliedEndTags('dd');

                    $currentNode = $this->openElements->bottom();

                    // If the current node is not a dd element, then this is a
                    // parse error.
                    if (
                        !($currentNode instanceof HTMLElement && $currentNode->localName === 'dd')
                    ) {
                        // Parse error.
                    }

                    // Pop elements from the stack of open elements until a dd
                    // element has been popped from the stack.
                    while (!$this->openElements->isEmpty()) {
                        $popped = $this->openElements->pop();

                        if ($popped instanceof HTMLElement && $popped->localName === 'dd') {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                if ($node instanceof HTMLElement && $node->localName === 'dt') {
                    // Generate implied end tags, except for dt elements.
                    $this->generateImpliedEndTags('dt');

                    $currentNode = $this->openElements->bottom();

                    // If the current node is not a dt element, then this is a
                    // parse error.
                    if (
                        !($currentNode instanceof HTMLElement && $currentNode->localName === 'dt')
                    ) {
                        // Parse error
                    }

                    // Pop elements from the stack of open elements until a dt
                    // element has been popped from the stack.
                    while (!$this->openElements->isEmpty()) {
                        $popped = $this->openElements->pop();

                        if ($popped instanceof HTMLElement && $popped->localName === 'dt') {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                // If node is in the special category, but is not an address,
                // div, or p element, then jump to the step labeled done below.
                // Otherwise, set node to the previous entry in the stack of
                // open elements and return to the step labeled loop.
                if (
                    $this->isSpecialNode($node)
                    && !($node instanceof HTMLElement
                        && (
                            ($name = $node->localName) === 'address'
                            || $name === 'div'
                            || $name === 'p'
                        )
                    )
                ) {
                    break;
                }
            }

            // Step "Done".
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement();
            }

            // Finally, insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof StartTagToken && $tagName === 'plaintext') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Switch the tokenizer to the PLAINTEXT state.
            // NOTE: Once a start tag with the tag name "plaintext" has been
            // seen, that will be the last token ever seen other than character
            // tokens (and the end-of-file token), because there is no way to
            // switch out of the PLAINTEXT state.
            $this->state->tokenizerState = TokenizerState::PLAINTEXT;
        } elseif ($token instanceof StartTagToken && $tagName === 'button') {
            // If the stack of open elements has a button element in scope,
            // then run these substeps:
            if ($this->openElements->hasElementInScope('button', Namespaces::HTML)) {
                // Parse error.
                // Generate implied end tags.
                $this->generateImpliedEndTags();

                // Pop elements from the stack of open elements until a button
                // element has been popped from the stack.
                while (!$this->openElements->isEmpty()) {
                    $popped = $this->openElements->pop();

                    if ($popped instanceof HTMLButtonElement) {
                        break;
                    }
                }
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';
        } elseif (
            $token instanceof EndTagToken
            && preg_match(
                '/^(address|article|aside|blockquote|button|center|details|'
                . 'dialog|dir|div|dl|fieldset|figcaption|figure|footer|header|'
                . 'hgroup|listing|main|menu|nav|ol|pre|section|summary|ul)$/',
                $tagName
            )
        ) {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInScope($tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            if (!$this->isHTMLElementWithName($this->openElements->bottom(), $tagName)) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped
            // from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->isHTMLElementWithName($this->openElements->pop(), $tagName)) {
                    break;
                }
            }
        } elseif ($token instanceof EndTagToken && $tagName === 'form') {
            if (!$this->openElements->containsTemplateElement()) {
                // Let node be the element that the form element pointer is set
                // to, or null if it is not set to an element.
                $node = $this->state->formElementPointer;

                // Set the form element pointer to null.
                $this->state->formElementPointer = null;

                // If node is null or if the stack of open elements does not
                // have node in scope, then this is a parse error; abort these
                // steps and ignore the token.
                if (
                    $node === null
                    || !$this->openElements->hasElementInScope($node->localName, Namespaces::HTML)
                ) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Generate implied end tags.
                $this->generateImpliedEndTags();

                // If the current node is not node, then this is a parse error.
                if ($this->openElements->bottom() !== $node) {
                    // Parse error.
                }

                // Remove node from the stack of open elements.
                $this->openElements->remove($node);

                return;
            }

            // If the stack of open elements does not have a form element
            // in scope, then this is a parse error; abort these steps and
            // ignore the token.
            if (!$this->openElements->hasElementInScope('form', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // If the current node is not a form element, then this is a parse
            // error.
            if (!$this->openElements->bottom() instanceof HTMLFormElement) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until a form
            // element has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->openElements->pop() instanceof HTMLFormElement) {
                    break;
                }
            }
        } elseif ($token instanceof EndTagToken && $tagName === 'p') {
            // If the stack of open elements does not have a p element in
            // button scope, then this is a parse error; insert an HTML element
            // for a "p" start tag token with no attributes.
            if (!$this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                // Parse error.
                $this->insertForeignElement(
                    new StartTagToken('p'),
                    Namespaces::HTML
                );
            }

            // Close a p element.
            $this->closePElement();
        } elseif ($token instanceof EndTagToken && $tagName === 'li') {
            // If the stack of open elements does not have an li element
            // in list item scope, then this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInListItemScope('li', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags, except for li elements.
            $this->generateImpliedEndTags('li');

            // If the current node is not an li element, then this is a parse
            // error.
            if (!$this->openElements->bottom() instanceof HTMLLIElement) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an li element
            // has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->openElements->pop() instanceof HTMLLIElement) {
                    break;
                }
            }
        } elseif ($token instanceof EndTagToken && ($tagName === 'dd' || $tagName === 'dt')) {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInScope($tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags, except for HTML elements with the
            // same tag name as the token.
            $this->generateImpliedEndTags($tagName);

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            if (!$this->isHTMLElementWithName($this->openElements->bottom(), $tagName)) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped from
            // the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->isHTMLElementWithName($this->openElements->pop(), $tagName)) {
                    break;
                }
            }
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'h1'
                || $tagName === 'h2'
                || $tagName === 'h3'
                || $tagName === 'h4'
                || $tagName === 'h5'
                || $tagName === 'h6'
            )
        ) {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element and whose tag name is one of "h1",
            // "h2", "h3", "h4", "h5", or "h6", then this is a parse error;
            // ignore the token.
            if (
                !$this->openElements->hasElementInScope('h1', Namespaces::HTML)
                && !$this->openElements->hasElementInScope('h2', Namespaces::HTML)
                && !$this->openElements->hasElementInScope('h3', Namespaces::HTML)
                && !$this->openElements->hasElementInScope('h4', Namespaces::HTML)
                && !$this->openElements->hasElementInScope('h5', Namespaces::HTML)
                && !$this->openElements->hasElementInScope('h6', Namespaces::HTML)
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            if (!$this->isHTMLElementWithName($this->openElements->bottom(), $tagName)) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element whose tag name is one of "h1", "h2", "h3", "h4", "h5",
            // or "h6" has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->openElements->pop() instanceof HTMLHeadingElement) {
                    break;
                }
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'a') {
            // If the list of active formatting elements contains an a element
            // between the end of the list and the last marker on the list (or
            // the start of the list if there is no marker on the list), then
            // this is a parse error; run the adoption agency algorithm for the
            // token, then remove that element from the list of active
            // formatting elements and the stack of open elements if the
            // adoption agency algorithm didn't already remove it (it might not
            // have if the element is not in table scope).
            if (!$this->activeFormattingElements->isEmpty()) {
                $hasAnchorElement = false;
                $element = null;

                foreach ($this->activeFormattingElements as $element) {
                    if ($element instanceof Marker) {
                        break;
                    } elseif ($element instanceof HTMLAnchorElement) {
                        $hasAnchorElement = true;

                        break;
                    }
                }

                if ($hasAnchorElement) {
                    // Parse error.
                    $this->adoptionAgency($token);

                    if ($element !== null) {
                        $this->activeFormattingElements->remove($element);
                        $this->openElements->remove($element);
                    }
                }
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($token, Namespaces::HTML);
            $this->activeFormattingElements->push($node);
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'b'
                || $tagName === 'big'
                || $tagName === 'code'
                || $tagName === 'em'
                || $tagName === 'font'
                || $tagName === 'i'
                || $tagName === 's'
                || $tagName === 'small'
                || $tagName === 'strike'
                || $tagName === 'strong'
                || $tagName === 'tt'
                || $tagName === 'u'
            )
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($token, Namespaces::HTML);
            $this->activeFormattingElements->push($node);
        } elseif ($token instanceof StartTagToken && $tagName === 'nobr') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // If the stack of open elements has a nobr element in scope,
            // then this is a parse error; run the adoption agency algorithm for
            // the token, then once again reconstruct the active formatting
            // elements, if any.
            if ($this->openElements->hasElementInScope('nobr', Namespaces::HTML)) {
                // Parse error.
                $this->adoptionAgency($token);
                $this->reconstructActiveFormattingElements();
            }

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($token, Namespaces::HTML);
            $this->activeFormattingElements->push($node);
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'a'
                || $tagName === 'b'
                || $tagName === 'big'
                || $tagName === 'code'
                || $tagName === 'em'
                || $tagName === 'font'
                || $tagName === 'i'
                || $tagName === 'nobr'
                || $tagName === 's'
                || $tagName === 'small'
                || $tagName === 'strike'
                || $tagName === 'strong'
                || $tagName === 'tt'
                || $tagName === 'u'
            )
        ) {
            // Run the adoption agency algorithm for the token.
            $this->adoptionAgency($token);
        } elseif (
            $token instanceof StartTagToken
            && ($tagName === 'applet' || $tagName === 'marquee' || $tagName === 'object')
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Insert a marker at the end of the list of active formatting
            // elements.
            $this->activeFormattingElements->push(new Marker());

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';
        } elseif (
            $token instanceof EndTagToken
            && ($tagName === 'applet' || $tagName === 'marquee' || $tagName === 'object')
        ) {
            // If the stack of open elements does not have an element in scope
            // that is an HTML element with the same tag name as that of the
            // token, then this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInScope($tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            if (!$this->isHTMLElementWithName($this->openElements->bottom(), $tagName)) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped from
            // the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->isHTMLElementWithName($this->openElements->pop(), $tagName)) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $this->activeFormattingElements->clearUpToLastMarker();
        } elseif ($token instanceof StartTagToken && $tagName === 'table') {
            // If the Document is not set to quirks mode, and the stack of
            // open elements has a p element in button scope, then close a p
            // element.
            if (
                $this->document->getMode() !== DocumentMode::QUIRKS
                && $this->openElements->hasElementInButtonScope('p', Namespaces::HTML)
            ) {
                $this->closePElement();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';

            // Switch the insertion mode to "in table".
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE;
        } elseif ($token instanceof EndTagToken && $tagName === 'br') {
            // Parse error.
            // Drop the attributes from the token, and act as described in the
            // next entry; i.e. act as if this was a "br" start tag token with
            // no attributes, rather than the end tag token that it actually is.
            $token->clearAttributes();

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'area'
                || $tagName === 'br'
                || $tagName === 'embed'
                || $tagName === 'img'
                || $tagName === 'keygen'
                || $tagName === 'wbr'
            )
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';
        } elseif ($token instanceof StartTagToken && $tagName === 'input') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // If the token does not have an attribute with the name "type", or
            // if it does, but that attribute's value is not an ASCII
            // case-insensitive match for the string "hidden", then: set the
            // frameset-ok flag to "not ok".
            $typeAttribute = null;

            foreach ($token->attributes as $attr) {
                if ($attr->name === 'type') {
                    $typeAttribute = $attr;

                    break;
                }
            }

            if (
                $typeAttribute === null
                || Utils::toASCIILowercase($typeAttribute->value) === 'hidden'
            ) {
                $this->framesetOk = 'not ok';
            }
        } elseif (
            $token instanceof StartTagToken
            && ($tagName === 'param' || $tagName === 'source' || $tagName === 'track')
        ) {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'hr') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement();
            }

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';
        } elseif ($token instanceof StartTagToken && $tagName === 'image') {
            // Parse error.
            // Change the token's tag name to "img" and reprocess it. (Don't
            // ask.)
            $token->tagName = 'img';
            $this->run($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'textarea') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // TODO: If the next token is a U+000A LINE FEED (LF) character
            // token, then ignore that token and move on to the next one.
            // (Newlines at the start of textarea elements are ignored as an
            // authoring convenience.)

            // Switch the tokenizer to the RCDATA state.
            $this->state->tokenizerState = TokenizerState::RCDATA;

            // Let the original insertion mode be the current insertion mode.
            $this->originalInsertionMode = $this->state->insertionMode;

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';

            // Switch the insertion mode to "text".
            $this->state->insertionMode = ParserInsertionMode::TEXT;
        } elseif ($token instanceof StartTagToken && $tagName === 'xmp') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement();
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Set the frameset-ok flag to "not ok
            $this->framesetOk = 'not ok';

            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement($token, self::RAW_TEXT_ELEMENT_ALGORITHM);
        } elseif ($token instanceof StartTagToken && $tagName === 'iframe') {
            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';

            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement(
                $token,
                self::RAW_TEXT_ELEMENT_ALGORITHM
            );
        } elseif (
            $token instanceof StartTagToken
            && ($tagName === 'noembed' || ($tagName === 'noscript' && $this->isScriptingEnabled))
        ) {
            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement($token, self::RAW_TEXT_ELEMENT_ALGORITHM);
        } elseif ($token instanceof StartTagToken && $tagName === 'select') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';

            // If the insertion mode is one of "in table", "in caption",
            // "in table body", "in row", or "in cell", then switch the
            // insertion mode to "in select in table". Otherwise, switch the
            // insertion mode to "in select".
            switch ($this->state->insertionMode) {
                case ParserInsertionMode::IN_TABLE:
                case ParserInsertionMode::IN_CAPTION:
                case ParserInsertionMode::IN_TABLE_BODY:
                case ParserInsertionMode::IN_ROW:
                case ParserInsertionMode::IN_CELL:
                    $this->state->insertionMode = ParserInsertionMode::IN_SELECT_IN_TABLE;

                    break;

                default:
                    $this->state->insertionMode = ParserInsertionMode::IN_SELECT;
            }
        } elseif (
            $token instanceof StartTagToken
            && ($tagName === 'optgroup' || $tagName === 'option')
        ) {
            // If the current node is an option element, then pop the current
            // node off the stack of open elements.
            if ($this->openElements->bottom() instanceof HTMLOptionElement) {
                $this->openElements->pop();
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof StartTagToken && ($tagName === 'rb' || $tagName === 'rtc')) {
            // If the stack of open elements has a ruby element in scope,
            // then generate implied end tags
            if ($this->openElements->hasElementInScope('ruby', Namespaces::HTML)) {
                $this->generateImpliedEndTags();
                $currentNode = $this->openElements->bottom();

                // If the current node is not now a ruby element, this is a
                // parse error.
                if (!($currentNode instanceof HTMLElement && $currentNode->localName === 'ruby')) {
                    // Parse error.
                }
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof StartTagToken && ($tagName === 'rp' || $tagName === 'rt')) {
            // If the stack of open elements has a ruby element in scope,
            // then generate implied end tags, except for rtc elements.
            if ($this->openElements->hasElementInScope('ruby', Namespaces::HTML)) {
                $this->generateImpliedEndTags('rtc');
                $currentNode = $this->openElements->bottom();

                // If the current node is not now a rtc element or a ruby
                // element, this is a parse error.
                if (!($currentNode instanceof HTMLElement && $currentNode->localName === 'rtc')) {
                    // Parse error.
                }
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof StartTagToken && $tagName === 'math') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Adjust MathML attributes for the token. (This fixes the case of
            // MathML attributes that are not all lowercase.)
            $this->adjustMathMLAttributes($token);

            // Adjust foreign attributes for the token. (This fixes the use of
            // namespaced attributes, in particular XLink.)
            $this->adjustForeignAttributes($token);

            // Insert a foreign element for the token, in the MathML namespace.
            $this->insertForeignElement($token, Namespaces::MATHML);

            // If the token has its self-closing flag set, pop the current node
            // off the stack of open elements and acknowledge the token's
            // self-closing flag.
            if ($token->isSelfClosing()) {
                $this->openElements->pop();
                $token->acknowledge();
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'svg') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Adjust SVG attributes for the token. (This fixes the case of SVG
            // attributes that are not all lowercase.)
            $this->adjustSVGAttributes($token);

            // Adjust foreign attributes for the token. (This fixes the use of
            // namespaced attributes, in particular XLink in SVG.)
            $this->adjustForeignAttributes($token);

            // Insert a foreign element for the token, in the SVG namespace.
            $this->insertForeignElement($token, Namespaces::SVG);

            // If the token has its self-closing flag set, pop the current node
            // off the stack of open elements and acknowledge the token's
            // self-closing flag.
            if ($token->isSelfClosing()) {
                $this->openElements->pop();
                $token->acknowledge();
            }
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'caption'
                || $tagName === 'col'
                || $tagName === 'colgroup'
                || $tagName === 'frame'
                || $tagName === 'head'
                || $tagName === 'tbody'
                || $tagName === 'td'
                || $tagName === 'tfoot'
                || $tagName === 'th'
                || $tagName === 'thead'
                || $tagName === 'tr'
            )
        ) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            // NOTE: This element will be an ordinary element.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof EndTagToken) {
            $this->applyAnyOtherEndTagForInBodyInsertionMode($token);
        }
    }

    private function applyAnyOtherEndTagForInBodyInsertionMode(EndTagToken $token): void
    {
        // Initialise node to be the current node (the bottommost node of the
        // stack).
        $tagName = $token->tagName;

        foreach ($this->openElements as $node) {
            if ($this->isHTMLElementWithName($node, $tagName)) {
                // Generate implied end tags, except for HTML elements with
                // the same tag name as the token.
                $this->generateImpliedEndTags($tagName);

                // If node is not the current node, then this is a parse error.
                if ($node !== $this->openElements->bottom()) {
                    // Parse error.
                }

                // Pop all the nodes from the current node up to node, including
                // node, then stop these steps.
                while (!$this->openElements->isEmpty()) {
                    if ($this->openElements->pop() === $node) {
                        break 2;
                    }
                }
            } elseif ($this->isSpecialNode($node)) {
                // Parse error.
                // Ignore the token.
                break;
            }
        }
    }

    /**
     * Closes a paragraph <p> element.
     *
     * @see https://html.spec.whatwg.org/multipage/#close-a-p-element
     */
    private function closePElement(): void
    {
        $this->generateImpliedEndTags('p');

        if (!$this->openElements->bottom() instanceof HTMLParagraphElement) {
            // Parse error
        }

        while (!$this->openElements->isEmpty()) {
            if ($this->openElements->pop() instanceof HTMLParagraphElement) {
                break;
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adoption-agency-algorithm
     */
    private function adoptionAgency(TagToken $token): void
    {
        $subject = $token->tagName;
        $currentNode = $this->openElements->bottom();

        // If the current node is an HTML Element with a tag name that matches
        // subject and the current node is not in the list of active formatting
        // elements, then remove the current node from the stack of open
        // elements and abort these steps.
        if (
            $this->isHTMLElementWithName($currentNode, $subject)
            && !$this->activeFormattingElements->contains($currentNode)
        ) {
            $this->openElements->pop();

            return;
        }

        // Let outer loop counter be zero.
        $outerLoopCounter = 0;

        // Outer loop
        while (true) {
            // If outer loop counter is greater than or equal to eight, then
            // abort these steps.
            if ($outerLoopCounter >= 8) {
                return;
            }

            // Increment outer loop counter by one.
            $outerLoopCounter++;

            // Let formatting element be the last element in the list of active
            // formatting elements that is between the end of the list and the
            // last marker in the list, if any, or the start of the list
            // otherwise, and has the tag name subject.
            $formattingElement = null;

            foreach ($this->activeFormattingElements as $e) {
                // TODO: Spec says use tag name, but it is broken unless I use
                // local name.
                if ($e instanceof Element && $e->localName === $subject) {
                    $formattingElement = $e;

                    break;
                } elseif ($e instanceof Marker) {
                    break;
                }
            }

            // If there is no such element, then abort these steps and instead
            // act as described in the "any other end tag" entry above.
            if (!$formattingElement) {
                $this->applyAnyOtherEndTagForInBodyInsertionMode($token);

                return;
            }

            // If formatting element is not in the stack of open elements, then
            // this is a parse error; remove the element from the list, and
            // abort these steps.
            if (!$this->openElements->contains($formattingElement)) {
                // Parse error.
                $this->activeFormattingElements->remove($formattingElement);

                return;
            }

            // If formatting element is in the stack of open elements, but
            // the element is not in scope, then this is a parse error; abort
            // these steps.
            if (
                $this->openElements->contains($formattingElement)
                && !$this->openElements->hasElementInScope(
                    $formattingElement->localName,
                    $formattingElement->namespaceURI
                )
            ) {
                // Parse error.
                return;
            }

            // If formatting element is not the current node, this is a parse
            // error. (But do not abort these steps.)
            if ($this->openElements->bottom() !== $formattingElement) {
                // Parse error.
            }

            // Let furthest block be the topmost node in the stack of open
            // elements that is lower in the stack than formatting element, and
            // is an element in the special category. There might not be one.
            $furthestBlock = null;
            $formattingElementIndex = $this->openElements->indexOf($formattingElement);
            $count = count($this->openElements);

            for ($i = $formattingElementIndex + 1; $i < $count; $i++) {
                $current = $this->openElements[$i];

                if ($this->isSpecialNode($current)) {
                    $furthestBlock = $current;

                    break;
                }
            }

            // If there is no furthest block, then the UA must first pop all the
            // nodes from the bottom of the stack of open elements, from the
            // current node up to and including formatting element, then remove
            // formatting element from the list of active formatting elements,
            // and finally abort these steps.
            if (!$furthestBlock) {
                while (!$this->openElements->isEmpty()) {
                    if ($this->openElements->pop() === $formattingElement) {
                        break;
                    }
                }

                $this->activeFormattingElements->remove($formattingElement);

                return;
            }

            // Let common ancestor be the element immediately above formatting
            // element in the stack of open elements.
            $commonAncestor = $this->openElements[$formattingElementIndex - 1];

            // Let a bookmark note the position of formatting element in the
            // list of active formatting elements relative to the elements on
            // either side of it in the list.
            $bookmark = new Bookmark();
            $this->activeFormattingElements->insertAfter($bookmark, $formattingElement);

            // Let node and last node be furthest block.
            $node = $furthestBlock;
            $lastNode = $furthestBlock;

            // Let inner loop counter be zero.
            $innerLoopCounter = 0;
            $clonedStack = clone $this->openElements;

            // Inner loop
            while (true) {
                // Increment inner loop counter by one.
                $innerLoopCounter++;

                // Let node be the element immediately above node in the stack
                // of open elements, or if node is no longer in the stack of
                // open elements (e.g. because it got removed by this
                // algorithm), the element that was immediately above node in
                // the stack of open elements before node was removed.
                $targetStack = !$this->openElements->contains($node)
                    ? $clonedStack
                    : $this->openElements;
                $node = $targetStack[$targetStack->indexOf($node) - 1];

                // If node is formatting element, then go to the next step in
                // the overall algorithm.
                if ($node === $formattingElement) {
                    break;
                }

                // If inner loop counter is greater than three and node is in
                // the list of active formatting elements, then remove node from
                // the list of active formatting elements.
                $nodeInList = $this->activeFormattingElements->contains($node);

                if ($innerLoopCounter > 3 && $nodeInList) {
                    $this->activeFormattingElements->remove($node);
                    $nodeInList = false;
                }

                // If node is not in the list of active formatting elements,
                // then remove node from the stack of open elements and then go
                // back to the step labeled inner loop.
                if (!$nodeInList) {
                    $this->openElements->remove($node);

                    continue;
                }

                // Create an element for the token for which the element node
                // was created, in the HTML namespace, with common ancestor as
                // the intended parent; replace the entry for node in the list
                // of active formatting elements with an entry for the new
                // element, replace the entry for node in the stack of open
                // elements with an entry for the new element, and let node be
                // the new element.
                $newElement = $this->createElementForToken(
                    $this->tokenRepository[$node],
                    Namespaces::HTML,
                    $commonAncestor
                );
                $this->tokenRepository->attach($newElement, $this->tokenRepository[$node]);

                $this->activeFormattingElements->replace($newElement, $node);
                $this->openElements->replace($newElement, $node);
                $node = $newElement;

                // If last node is furthest block, then move the aforementioned
                // bookmark to be immediately after the new node in the list of
                // active formatting elements.
                if ($lastNode === $furthestBlock) {
                    $this->activeFormattingElements->remove($bookmark);
                    $this->activeFormattingElements->insertAfter($bookmark, $newElement);
                }

                // Insert last node into node, first removing it from its
                // previous parent node if any.
                $node->appendChild($lastNode);

                // Let last node be node.
                $lastNode = $node;
            }

            // Insert whatever last node ended up being in the previous step at
            // the appropriate place for inserting a node, but using common
            // ancestor as the override target.
            $this->insertNode(
                $lastNode,
                $this->getAppropriatePlaceForInsertingNode($commonAncestor)
            );

            // Create an element for the token for which formatting element was
            // created, in the HTML namespace, with furthest block as the
            // intended parent.
            $element = $this->createElementForToken(
                $this->tokenRepository[$formattingElement],
                Namespaces::HTML,
                $furthestBlock
            );

            // Take all of the child nodes of furthest block and append them to
            // the element created in the last step.
            foreach ($furthestBlock->childNodes as $child) {
                $element->appendChild($child);
            }

            // Append that new element to furthest block.
            $furthestBlock->appendChild($element);

            // Remove formatting element from the list of active formatting
            // elements, and insert the new element into the list of active
            // formatting elements at the position of the aforementioned
            // bookmark.
            $this->activeFormattingElements->remove($formattingElement);
            $this->activeFormattingElements->replace($element, $bookmark);

            // Remove formatting element from the stack of open elements, and
            // insert the new element into the stack of open elements
            // immediately below the position of furthest block in that stack.
            $this->openElements->remove($formattingElement);
            $this->openElements->insertAfter($element, $furthestBlock);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incdata
     */
    private function inTextInsertionMode(Token $token): void
    {
        if ($token instanceof CharacterToken) {
            // Insert the token's character.
            // NOTE: This can never be a U+0000 NULL character; the tokenizer
            // converts those to U+FFFD REPLACEMENT CHARACTER characters.
            $this->insertCharacter($token);
        } elseif ($token instanceof EOFToken) {
            // Parse error.
            // If the current node is a script element, mark the script element
            // as "already started".
            if ($this->openElements->bottom() instanceof HTMLScriptElement) {
                // TODO: Mark the script element as "already started".
            }

            // Pop the current node off the stack of open elements.
            $this->openElements->pop();

            // Switch the insertion mode to the original insertion mode and
            // reprocess the token.
            $this->state->insertionMode = $this->originalInsertionMode;
            $this->run($token);
        } elseif ($token instanceof EndTagToken && $token->tagName === 'script') {
            // TODO: If the JavaScript execution context stack is empty, perform
            // a microtask checkpoint.

            // Let script be the current node (which will be a script element).
            $script = $this->openElements->bottom();

            // Pop the current node off the stack of open elements.
            $this->openElements->pop();

            // Switch the insertion mode to the original insertion mode.
            $this->state->insertionMode = $this->originalInsertionMode;

            // TODO: More stuff that will probably never be fully supported
        } elseif ($token instanceof EndTagToken) {
            // Pop the current node off the stack of open elements.
            $this->openElements->pop();

            // Switch the insertion mode to the original insertion mode.
            $this->state->insertionMode = $this->originalInsertionMode;
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intable
     */
    private function inTableInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            && ($currentNode = $this->openElements->bottom())
            && (
                $currentNode instanceof HTMLTableElement
                || $currentNode instanceof HTMLTableSectionElement
                || $currentNode instanceof HTMLTableRowElement
            )
        ) {
            // Let the pending table character tokens be an empty list of
            // tokens.
            $this->pendingTableCharacterTokens = [];

            // Let the original insertion mode be the current insertion mode.
            $this->originalInsertionMode = $this->state->insertionMode;

            // Switch the insertion mode to "in table text" and reprocess the
            // token.
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE_TEXT;
            $this->run($token);
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'caption') {
            // Clear the stack back to a table context.
            $this->openElements->clearBackToTableContext();

            // Insert a marker at the end of the list of active formatting
            // elements.
            $this->activeFormattingElements->push(new Marker());

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in caption".
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->state->insertionMode = ParserInsertionMode::IN_CAPTION;
        } elseif ($token instanceof StartTagToken && $tagName === 'colgroup') {
            // Clear the stack back to a table context.
            $this->openElements->clearBackToTableContext();

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in column group".
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->state->insertionMode = ParserInsertionMode::IN_COLUMN_GROUP;
        } elseif ($token instanceof StartTagToken && $tagName === 'col') {
            // Clear the stack back to a table context.
            $this->openElements->clearBackToTableContext();

            // Insert an HTML element for a "colgroup" start tag token with no
            // attributes, then switch the insertion mode to "in column group".
            $this->insertForeignElement(new StartTagToken('colgroup'), Namespaces::HTML);
            $this->state->insertionMode = ParserInsertionMode::IN_COLUMN_GROUP;

            // Reprocess the current token.
            $this->run($token);
        } elseif (
            $token instanceof StartTagToken
            && ($tagName === 'tbody' || $tagName === 'tfoot' || $tagName === 'thead')
        ) {
            // Clear the stack back to a table context.
            $this->openElements->clearBackToTableContext();

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in table body".
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE_BODY;
        } elseif (
            $token instanceof StartTagToken
            && ($tagName === 'td' || $tagName === 'th' || $tagName === 'tr')
        ) {
            // Clear the stack back to a table context.
            $this->openElements->clearBackToTableContext();

            // Insert an HTML element for a "tbody" start tag token with no
            // attributes, then switch the insertion mode to "in table body".
            $this->insertForeignElement(new StartTagToken('tbody'), Namespaces::HTML);
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE_BODY;

            // Reprocess the current token.
            $this->run($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'table') {
            // Parse error.
            // If the stack of open elements does not have a table element
            // in table scope, ignore the token.
            if (!$this->openElements->hasElementInTableScope('table', Namespaces::HTML)) {
                // Ignore the token.
                return;
            }

            // Pop elements from this stack until a table element has been
            // popped from the stack.
            while (!$this->openElements->isEmpty()) {
                $popped = $this->openElements->pop();

                if ($popped instanceof HTMLTableElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->resetInsertionMode();

            // Reprocess the token.
            $this->run($token);
        } elseif ($token instanceof EndTagToken && $tagName === 'table') {
            // If the stack of open elements does not have a table element in
            // table scope, this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInTableScope('table', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Pop elements from this stack until a table element has been
            // popped from the stack.
            while (!$this->openElements->isEmpty()) {
                $popped = $this->openElements->pop();

                if ($popped instanceof HTMLTableElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->resetInsertionMode();
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'body'
                || $tagName === 'caption'
                || $tagName === 'col'
                || $tagName === 'colgroup'
                || $tagName === 'html'
                || $tagName === 'tbody'
                || $tagName === 'td'
                || $tagName === 'tfoot'
                || $tagName === 'th'
                || $tagName === 'thead'
                || $tagName === 'tr'
            )
        ) {
            // Parse error.
            // Ignore the token.
        } elseif (
            (
                $token instanceof StartTagToken
                && ($tagName === 'style' || $tagName === 'script' || $tagName === 'template')
            )
            || ($token instanceof EndTagToken && $tagName === 'template')
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'input') {
            // If the token does not have an attribute with the name "type", or
            // if it does, but that attribute's value is not an ASCII
            // case-insensitive match for the string "hidden", then: act as
            // described in the "anything else" entry below.
            $typeAttr = null;

            foreach ($token->attributes as $attr) {
                if ($attr->name === 'type') {
                    $typeAttr = $attr;

                    break;
                }
            }

            if ($typeAttr === null || Utils::toASCIILowercase($typeAttr->value) !== 'hidden') {
                $this->inTableInsertionModeAnythingElse($token);

                return;
            }

            // Parse error.
            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);

            // Pop that input element off the stack of open elements.
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'form') {
            // Parse error.
            // If there is a template element on the stack of open elements, or
            // if the form element pointer is not null, ignore the token.
            if (
                $this->openElements->containsTemplateElement()
                || $this->state->formElementPointer !== null
            ) {
                // Ignore the token.
                return;
            }

            // Insert an HTML element for the token, and set the form element
            // pointer to point to the element created.
            $this->state->formElementPointer = $this->insertForeignElement(
                $token,
                Namespaces::HTML
            );

            // Pop that form element off the stack of open elements.
            $this->openElements->pop();
        } elseif ($token instanceof EOFToken) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } else {
            $this->inTableInsertionModeAnythingElse($token);
        }
    }

    /**
     * The "in table" insertion mode's "anything else" steps.
     */
    private function inTableInsertionModeAnythingElse(Token $token): void
    {
        // Parse error.
        // Enable foster parenting, process the token using the rules for
        // the "in body" insertion mode, and then disable foster parenting.
        $this->fosterParenting = true;
        $this->inBodyInsertionMode($token);
        $this->fosterParenting = false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intabletext
     */
    private function inTableTextInsertionMode(Token $token): void
    {
        if ($token instanceof CharacterToken) {
            if ($token->data === "\x00") {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Append the character token to the pending table character tokens
            // list.
            $this->pendingTableCharacterTokens[] = $token;
        } else {
            // If any of the tokens in the pending table character tokens list
            // are character tokens that are not space characters, then this is
            // a parse error: reprocess the character tokens in the pending
            // table character tokens list using the rules given in the
            // "anything else" entry in the "in table" insertion mode.
            // Otherwise, insert the characters given by the pending table
            // character tokens list.
            $methodName = 'insertCharacter';

            foreach ($this->pendingTableCharacterTokens as $characterToken) {
                $data = $characterToken->data;

                if (
                    $data !== "\x09"
                    && $data !== "\x0A"
                    && $data !== "\x0C"
                    && $data !== "\x0D"
                    && $data !== "\x20"
                ) {
                    $methodName = 'inTableInsertionModeAnythingElse';

                    break;
                }
            }

            foreach ($this->pendingTableCharacterTokens as $characterToken) {
                $this->{$methodName}($characterToken);
            }

            // Switch the insertion mode to the original insertion mode and
            // reprocess the token.
            $this->state->insertionMode = $this->originalInsertionMode;
            $this->run($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incaption
     */
    private function inCaptionInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof EndTagToken && $tagName === 'caption') {
            // If the stack of open elements does not have a caption element in
            // table scope, this is a parse error; ignore the token. (fragment
            // case)
            if (!$this->openElements->hasElementInTableScope('caption', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // Now, if the current node is not a caption element, then this is
            // a parse error.
            $currentNode = $this->openElements->bottom();

            if (!$currentNode instanceof HTMLTableCaptionElement) {
                // Parse error.
            }

            // Pop elements from this stack until a caption element has been
            // popped from the stack.
            while (!$this->openElements->isEmpty()) {
                $popped = $this->openElements->pop();

                if ($popped instanceof HTMLTableCaptionElement) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $this->activeFormattingElements->clearUpToLastMarker();

            // Switch the insertion mode to "in table".
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE;
        } elseif (
            (
                $token instanceof StartTagToken
                && (
                    $tagName === 'caption'
                    || $tagName === 'col'
                    || $tagName === 'colgroup'
                    || $tagName === 'tbody'
                    || $tagName === 'td'
                    || $tagName === 'tfoot'
                    || $tagName === 'th'
                    || $tagName === 'thead'
                    || $tagName === 'tr'
                )
            )
            || ($token instanceof EndTagToken && $tagName === 'table')
        ) {
            // If the stack of open elements does not have a caption element
            // in table scope, this is a parse error; ignore the token.
            // (fragment case)
            if (!$this->openElements->hasElementInTableScope('caption', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
            } else {
                // Generate implied end tags.
                $this->generateImpliedEndTags();

                // Now, if the current node is not a caption element, then this
                // is a parse error.
                $currentNode = $this->openElements->bottom();

                if (!$currentNode instanceof HTMLTableCaptionElement) {
                    // Parse error.
                }

                // Pop elements from this stack until a caption element has
                // been popped from the stack.
                while (!$this->openElements->isEmpty()) {
                    $popped = $this->openElements->pop();

                    if ($popped instanceof HTMLTableCaptionElement) {
                        break;
                    }
                }

                // Clear the list of active formatting elements up to the last
                // marker.
                $this->activeFormattingElements->clearUpToLastMarker();

                // Switch the insertion mode to "in table".
                $this->state->insertionMode = ParserInsertionMode::IN_TABLE;

                // Reprocess the token.
                $this->run($token);
            }
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'body'
                || $tagName === 'col'
                || $tagName === 'colgroup'
                || $tagName === 'html'
                || $tagName === 'tbody'
                || $tagName === 'td'
                || $tagName === 'tfoot'
                || $tagName === 'th'
                || $tagName === 'thead'
                || $tagName === 'tr'
            )
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incolgroup
     */
    private function inColumnGroupInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Insert the character.
            $this->insertCharacter($token);
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'col') {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }
        } elseif ($token instanceof EndTagToken && $tagName === 'colgroup') {
            // If the current node is not a colgroup element, then this is a
            // parse error; ignore the token.
            $currentNode = $this->openElements->bottom();

            if (
                !(
                    $currentNode instanceof HTMLTableColElement
                    && $currentNode->localName === 'colgroup'
                )
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, pop the current node from the stack of open elements.
            // Switch the insertion mode to "in table".
            $this->openElements->pop();
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE;
        } elseif ($token instanceof EndTagToken && $tagName === 'col') {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof TagToken && $tagName === 'template') {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif ($token instanceof EOFToken) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } else {
            // If the current node is not a colgroup element, then this is a
            // parse error; ignore the token.
            $currentNode = $this->openElements->bottom();

            if (
                !(
                    $currentNode instanceof HTMLTableColElement
                    && $currentNode->localName === 'colgroup'
                )
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, pop the current node from the stack of open
            // elements.
            $this->openElements->pop();

            // Switch the insertion mode to "in table".
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE;

            // Reprocess the token.
            $this->run($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intbody
     */
    private function inTableBodyInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof StartTagToken && $tagName === 'tr') {
            // Clear the stack back to a table body context.
            $this->openElements->clearBackToTableBodyContext();

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in row".
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->state->insertionMode = ParserInsertionMode::IN_ROW;
        } elseif ($token instanceof StartTagToken && ($tagName === 'th' || $tagName === 'td')) {
            // Parse error.
            // Clear the stack back to a table body context.
            $this->openElements->clearBackToTableBodyContext();

            // Insert an HTML element for a "tr" start tag token with no
            // attributes, then switch the insertion mode to "in row".
            $this->insertForeignElement(new StartTagToken('tr'), Namespaces::HTML);
            $this->state->insertionMode = ParserInsertionMode::IN_ROW;

            // Reprocess the current token.
            $this->run($token);
        } elseif (
            $token instanceof EndTagToken
            && ($tagName === 'tbody' || $tagName === 'thead' || $tagName === 'tfoot')
        ) {
            // If the stack of open elements does not have an element in table
            // scope that is an HTML element with the same tag name as the
            // token, this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInTableScope($tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table body context.
            $this->openElements->clearBackToTableBodyContext();

            // Pop the current node from the stack of open elements. Switch the
            // insertion mode to "in table".
            $this->openElements->pop();
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE;
        } elseif (
            (
                $token instanceof StartTagToken
                && (
                    $tagName === 'caption'
                    || $tagName === 'col'
                    || $tagName === 'colgroup'
                    || $tagName === 'tbody'
                    || $tagName === 'tfoot'
                    || $tagName === 'thead'
                )
            )
            || ($token instanceof EndTagToken && $tagName === 'table')
        ) {
            // If the stack of open elements does not have a tbody, thead, or
            // tfoot element in table scope, this is a parse error; ignore the
            // token.
            if (
                !$this->openElements->hasElementInTableScope('tbody', Namespaces::HTML)
                && !$this->openElements->hasElementInTableScope('tbody', Namespaces::HTML)
                && !$this->openElements->hasElementInTableScope('tbody', Namespaces::HTML)
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table body context.
            $this->openElements->clearBackToTableBodyContext();

            // Pop the current node from the stack of open elements. Switch the
            // insertion mode to "in table".
            $this->openElements->pop();
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE;

            // Reprocess the token.
            $this->run($token);
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'body'
                || $tagName === 'caption'
                || $tagName === 'col'
                || $tagName === 'colgroup'
                || $tagName === 'html'
                || $tagName === 'td'
                || $tagName === 'th'
                || $tagName === 'tr'
            )
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            // Process the token using the rules for the "in table" insertion
            // mode.
            $this->inTableInsertionMode($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intr
     */
    private function inRowInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof StartTagToken && ($tagName === 'th' || $tagName === 'td')) {
            // Clear the stack back to a table row context.
            $this->openElements->clearBackToTableRowContext();

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in cell".
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->state->insertionMode = ParserInsertionMode::IN_CELL;

            // Insert a marker at the end of the list of active formatting
            // elements.
            $this->activeFormattingElements->push(new Marker());
        } elseif ($token instanceof EndTagToken && $tagName === 'tr') {
            // If the stack of open elements does not have a tr element in
            // table scope, this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInTableScope('tr', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table row context.
            $this->openElements->clearBackToTableRowContext();

            // Pop the current node (which will be a tr element) from the stack
            // of open elements. Switch the insertion mode to "in table body".
            $this->openElements->pop();
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE_BODY;
        } elseif (
            (
                $token instanceof StartTagToken
                && (
                    $tagName === 'caption'
                    || $tagName === 'col'
                    || $tagName === 'colgroup'
                    || $tagName === 'tbody'
                    || $tagName === 'tfoot'
                    || $tagName === 'thead'
                    || $tagName === 'tr'
                )
            )
            || ($token instanceof EndTagToken && $tagName === 'table')
        ) {
            // If the stack of open elements does not have a tr element in
            // table scope, this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInTableScope('tr', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table row context.
            $this->openElements->clearBackToTableRowContext();

            // Pop the current node (which will be a tr element) from the stack
            // of open elements. Switch the insertion mode to "in table body".
            $this->openElements->pop();
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE_BODY;

            // Reprocess the token.
            $this->run($token);
        } elseif (
            $token instanceof EndTagToken
            && ($tagName === 'tbody' || $tagName === 'thead' || $tagName === 'tfoot')
        ) {
            // If the stack of open elements does not have an element in table
            // scope that is an HTML element with the same tag name as the
            // token, this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInTableScope($tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
            }

            // If the stack of open elements does not have a tr element in
            // table scope, ignore the token.
            if (!$this->openElements->hasElementInTableScope('tr', Namespaces::HTML)) {
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table row context.
            $this->openElements->clearBackToTableRowContext();

            // Pop the current node (which will be a tr element) from the stack
            // of open elements. Switch the insertion mode to "in table body".
            $this->openElements->pop();
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE_BODY;

            // Reprocess the token.
            $this->run($token);
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'body'
                || $tagName === 'caption'
                || $tagName === 'col'
                || $tagName === 'colgroup'
                || $tagName === 'html'
                || $tagName === 'td'
                || $tagName === 'th'
            )
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            // Process the token using the rules for the "in table" insertion
            // mode.
            $this->inTableInsertionMode($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intd
     */
    private function inCellInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof EndTagToken && ($tagName === 'td' || $tagName === 'th')) {
            // If the stack of open elements does not have an element in table
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInTableScope($tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // Now, if the current node is not an HTML element with the same
            // tag name as the token, then this is a parse error.
            if (!$this->isHTMLElementWithName($this->openElements->bottom(), $tagName)) {
                // Parse error
            }

            // Pop elements from the stack of open elements stack until an
            // HTML element with the same tag name as the token has been popped
            // from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->isHTMLElementWithName($this->openElements->pop(), $tagName)) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $this->activeFormattingElements->clearUpToLastMarker();

            // Switch the insertion mode to "in row".
            $this->state->insertionMode = ParserInsertionMode::IN_ROW;
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'caption'
                || $tagName === 'col'
                || $tagName === 'colgroup'
                || $tagName === 'tbody'
                || $tagName === 'td'
                || $tagName === 'tfoot'
                || $tagName === 'th'
                || $tagName === 'thead'
                || $tagName === 'tr'
            )
        ) {
            // If the stack of open elements does not have a td or th element
            // in table scope, then this is a parse error; ignore the token.
            // (fragment case)
            if (
                !$this->openElements->hasElementInTableScope('td', Namespaces::HTML)
                || !$this->openElements->hasElementInTableScope('th', Namespaces::HTML)
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, close the cell and reprocess the token.
            $this->closeCell();
            $this->run($token);
        } elseif (
            $token instanceof EndTagToken
            && ($tagName === 'body'
                || $tagName === 'caption'
                || $tagName === 'col'
                || $tagName === 'colgroup'
                || $tagName === 'html'
            )
        ) {
            // Parse error.
            // Ignore the token.
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'table'
                || $tagName === 'tbody'
                || $tagName === 'tfoot'
                || $tagName === 'thead'
                || $tagName === 'tr'
            )
        ) {
            // If the stack of open elements does not have an element in table
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (!$this->openElements->hasElementInTableScope($tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, close the cell and reprocess the token.
            $this->closeCell();
            $this->run($token);
        } else {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        }
    }

    /**
     * Performs the steps necessary to close a table cell (td) element.
     */
    private function closeCell(): void
    {
        // Generate implied end tags.
        $this->generateImpliedEndTags();

        // If the current node is not now a td element or a th element, then
        // this is a parse error.
        if (!$this->openElements->bottom() instanceof HTMLTableCellElement) {
            // Parse error.
        }

        // Pop elements from the stack of open elements stack until a td
        // element or a th element has been popped from the stack.
        while (!$this->openElements->isEmpty()) {
            if ($this->openElements->pop() instanceof HTMLTableCellElement) {
                break;
            }
        }

        // Clear the list of active formatting elements up to the last marker.
        $this->activeFormattingElements->clearUpToLastMarker();

        // Switch the insertion mode to "in row".
        $this->state->insertionMode = ParserInsertionMode::IN_ROW;

        // NOTE: The stack of open elements cannot have both a td and a th
        // element in table scope at the same time, nor can it have neither
        // when the close the cell algorithm is invoked.
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inselect
     */
    private function inSelectInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof CharacterToken && $token->data === "\x00") {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof CharacterToken) {
            // Insert the token's character.
            $this->insertCharacter($token);
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'option') {
            // If the current node is an option element, pop that node from the
            // stack of open elements.
            if ($this->openElements->bottom() instanceof HTMLOptionElement) {
                $this->openElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof StartTagToken && $tagName === 'optgroup') {
            // If the current node is an option element, pop that node from the
            // stack of open elements.
            if ($this->openElements->bottom() instanceof HTMLOptionElement) {
                $this->openElements->pop();
            }

            // If the current node is an optgroup element, pop that node from
            // the stack of open elements.
            if ($this->openElements->bottom() instanceof HTMLOptGroupElement) {
                $this->openElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof EndTagToken && $tagName === 'optgroup') {
            // First, if the current node is an option element, and the node
            // immediately before it in the stack of open elements is an
            // optgroup element, then pop the current node from the stack of
            // open elements.
            $iterator = $this->openElements->getIterator();
            $iterator->next();

            if (
                $this->openElements->bottom() instanceof HTMLOptionElement
                && $iterator->current() instanceof HTMLOptGroupElement
            ) {
                $this->openElements->pop();
            }

            // If the current node is an optgroup element, then pop that node
            // from the stack of open elements. Otherwise, this is a parse
            // error; ignore the token.
            if ($this->openElements->bottom() instanceof HTMLOptGroupElement) {
                $this->openElements->pop();
            } else {
                // Parse error.
                // Ignore the token.
            }
        } elseif ($token instanceof EndTagToken && $tagName === 'option') {
            // If the current node is an option element, then pop that node
            // from the stack of open elements. Otherwise, this is a parse
            // error; ignore the token.
            if ($this->openElements->bottom() instanceof HTMLOptionElement) {
                $this->openElements->pop();
            } else {
                // Parse error.
                // Ignore the token.
            }
        } elseif ($token instanceof EndTagToken && $tagName === 'select') {
            // If the stack of open elements does not have a select element in
            // select scope, this is a parse error; ignore the token. (fragment
            // case)
            if (!$this->openElements->hasElementInSelectScope('select', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->openElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->resetInsertionMode();
        } elseif ($token instanceof StartTagToken && $tagName === 'select') {
            // Parse error
            // If the stack of open elements does not have a select
            // element in select scope, ignore the token. (fragment case)
            if (!$this->openElements->hasElementInSelectScope('select', Namespaces::HTML)) {
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->openElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->resetInsertionMode();
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'input'
                || $tagName === 'keygen'
                || $tagName === 'textarea'
            )
        ) {
            // Parse error
            // If the stack of open elements does not have a select
            // element in select scope, ignore the token. (fragment case)
            if (!$this->openElements->hasElementInSelectScope('select', Namespaces::HTML)) {
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->openElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->resetInsertionMode();

            // Reprocess the token.
            $this->run($token);
        } elseif (
            ($token instanceof StartTagToken && $tagName === 'script')
            || ($token instanceof TagToken && $tagName === 'template')
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif ($token instanceof EOFToken) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } else {
            // Parse error.
            // Ignore the token.
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inselectintable
     */
    private function inSelectInTableInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof StartTagToken
            && (
                $tagName === 'caption'
                || $tagName === 'table'
                || $tagName === 'tbody'
                || $tagName === 'tfoot'
                || $tagName === 'thead'
                || $tagName === 'tr'
                || $tagName === 'td'
                || $tagName === 'th'
            )
        ) {
            // Parse error.
            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->openElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->resetInsertionMode();

            // Reprocess the token.
            $this->run($token);
        } elseif (
            $token instanceof EndTagToken
            && (
                $tagName === 'caption'
                || $tagName === 'table'
                || $tagName === 'tbody'
                || $tagName === 'tfoot'
                || $tagName === 'thead'
                || $tagName === 'tr'
                || $tagName === 'td'
                || $tagName === 'th'
            )
        ) {
            // Parse error
            // If the stack of open elements does not have an element in
            // table scope that is an HTML element with the same tag name as
            // that of the token, then ignore the token.
            if (!$this->openElements->hasElementInTableScope($tagName, Namespaces::HTML)) {
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                if ($this->openElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->resetInsertionMode();

            // Reprocess the token.
            $this->run($token);
        } else {
            // Process the token using the rules for the "in select" insertion
            // mode.
            $this->inSelectInsertionMode($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intemplate
     */
    private function inTemplateInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            || $token instanceof CommentToken
            || $token instanceof DoctypeToken
        ) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif (
            (
                $token instanceof StartTagToken
                && (
                    $tagName === 'base'
                    || $tagName === 'basefont'
                    || $tagName === 'bgsound'
                    || $tagName === 'link'
                    || $tagName === 'meta'
                    || $tagName === 'noframes'
                    || $tagName === 'script'
                    || $tagName === 'style'
                    || $tagName === 'template'
                    || $tagName === 'title'
                )
            )
            || ($token instanceof EndTagToken && $tagName === 'template')
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif (
            $token instanceof StartTagToken
            && (
                $tagName === 'caption'
                || $tagName === 'colgroup'
                || $tagName === 'tbody'
                || $tagName === 'tfoot'
                || $tagName === 'thead'
            )
        ) {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->templateInsertionModes->pop();

            // Push "in table" onto the stack of template insertion modes so
            // that it is the new current template insertion mode.
            $this->templateInsertionModes->push(ParserInsertionMode::IN_TABLE);

            // Switch the insertion mode to "in table", and reprocess the token.
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE;
            $this->run($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'col') {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->templateInsertionModes->pop();

            // Push "in column group" onto the stack of template insertion modes
            // so that it is the new current template insertion mode.
            $this->templateInsertionModes->push(
                ParserInsertionMode::IN_COLUMN_GROUP
            );

            // Switch the insertion mode to "in column group", and reprocess the
            // token.
            $this->state->insertionMode = ParserInsertionMode::IN_COLUMN_GROUP;
            $this->run($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'tr') {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->templateInsertionModes->pop();

            // Push "in table body" onto the stack of template insertion modes
            // so that it is the new current template insertion mode.
            $this->templateInsertionModes->push(
                ParserInsertionMode::IN_TABLE_BODY
            );

            // Switch the insertion mode to "in table body", and reprocess the
            // token.
            $this->state->insertionMode = ParserInsertionMode::IN_TABLE_BODY;
            $this->run($token);
        } elseif ($token instanceof StartTagToken && ($tagName === 'td' || $tagName === 'th')) {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->templateInsertionModes->pop();

            // Push "in row" onto the stack of template insertion modes so that
            // it is the new current template insertion mode.
            $this->templateInsertionModes->push(ParserInsertionMode::IN_ROW);

            // Switch the insertion mode to "in row", and reprocess the token.
            $this->state->insertionMode = ParserInsertionMode::IN_ROW;
            $this->run($token);
        } elseif ($token instanceof StartTagToken) {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->templateInsertionModes->pop();

            // Push "in body" onto the stack of template insertion modes so that
            // it is the new current template insertion mode.
            $this->templateInsertionModes->push(ParserInsertionMode::IN_BODY);

            // Switch the insertion mode to "in body", and reprocess the token.
            $this->state->insertionMode = ParserInsertionMode::IN_BODY;
            $this->run($token);
        } elseif ($token instanceof EndTagToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof EOFToken) {
            // If there is no template element on the stack of open elements,
            // then stop parsing. (fragment case)
            if (!$this->openElements->containsTemplateElement()) {
                $this->stopParsing();
            } else {
                // Parse  error
            }

            // Pop elements from the stack of open elements until a template
            // element has been popped from the stack.
            while (!$this->openElements->isEmpty()) {
                $popped = $this->openElements->pop();

                if ($popped instanceof HTMLTemplateElement) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $this->activeFormattingElements->clearUpToLastMarker();

            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->templateInsertionModes->pop();

            // Reset the insertion mode appropriately.
            $this->resetInsertionMode();

            // Reprocess the token.
            $this->run($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-afterbody
     */
    private function afterBodyInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof CommentToken) {
            // Insert a comment as the last child of the first element in the
            // stack of open elements (the html element).
            $this->insertComment($token, [$this->openElements[0], 'beforeend']);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof EndTagToken && $tagName === 'html') {
            // If the parser was originally created as part of the HTML fragment
            // parsing algorithm, this is a parse error; ignore the token.
            // (fragment case)
            if ($this->isFragmentCase) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, switch the insertion mode to "after after body".
            $this->state->insertionMode = ParserInsertionMode::AFTER_AFTER_BODY;
        } elseif ($token instanceof EOFToken) {
            // Stop parsing.
            $this->stopParsing();
        } else {
            // Parse error.
            // Switch the insertion mode to "in body" and reprocess the token.
            $this->state->insertionMode = ParserInsertionMode::IN_BODY;
            $this->run($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inframeset
     */
    private function inFramesetInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Insert the character.
            $this->insertCharacter($token);
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof StartTagToken && $tagName === 'frameset') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($token, Namespaces::HTML);
        } elseif ($token instanceof EndTagToken && $tagName === 'frameset') {
            // If the current node is the root html element, then this is a
            // parse error; ignore the token. (fragment case)
            if ($this->openElements->bottom() instanceof HTMLHtmlElement) {
                // Parse error.
                // Ignore the token.
            } else {
                // Otherwise, pop the current node from the stack of open
                // elements.
                $this->openElements->pop();
            }

            // If the parser was not originally created as part of the HTML
            // fragment parsing algorithm (fragment case), and the current node
            // is no longer a frameset element, then switch the insertion mode
            // to "after frameset".
            if (
                !$this->isFragmentCase
                && !$this->openElements->bottom() instanceof HTMLFrameSetElement
            ) {
                $this->state->insertionMode = ParserInsertionMode::AFTER_FRAMESET;
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'frame') {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $this->insertForeignElement($token, Namespaces::HTML);
            $this->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }
        } elseif ($token instanceof StartTagToken && $tagName === 'noframes') {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif ($token instanceof EOFToken) {
            // If the current node is not the root html element, then this is a
            // parse error.
            if (!$this->openElements->bottom() instanceof HTMLHtmlElement) {
                // Parse error.
            }

            // NOTE: The current node can only be the root html element in the
            // fragment case.

            // Stop parsing.
            $this->stopParsing();
        } else {
            // Parse error.
            // Ignore the token.
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-afterframeset
     */
    private function afterFramesetInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Insert the character.
            $this->insertCharacter($token);
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof EndTagToken && $tagName === 'html') {
            // Switch the insertion mode to "after after frameset".
            $this->state->insertionMode = ParserInsertionMode::AFTER_AFTER_FRAMESET;
        } elseif ($token instanceof StartTagToken && $tagName === 'noframes') {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } elseif ($token instanceof EOFToken) {
            // Stop parsing
            $this->stopParsing();
        } else {
            // Parse error.
            // Ignore the token.
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-after-body-insertion-mode
     */
    private function afterAfterBodyInsertionMode(Token $token): void
    {
        if ($token instanceof CommentToken) {
            // Insert a comment as the last child of the Document object.
            $this->insertComment($token, [$this->document, 'beforeend']);
        } elseif (
            $token instanceof DoctypeToken
            || (
                $token instanceof CharacterToken
                && (
                    ($data = $token->data) === "\x09"
                    || $data === "\x0A"
                    || $data === "\x0C"
                    || $data === "\x0D"
                    || $data === "\x20"
                )
            )
            || ($token instanceof StartTagToken && $token->tagName === 'html')
        ) {
            // Process the token using the rules for the "in body" insertion mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof EOFToken) {
            // Stop parsing
            $this->stopParsing();
        } else {
            // Parse error.
            // Switch the insertion mode to "in body" and reprocess the token.
            $this->state->insertionMode = ParserInsertionMode::IN_BODY;
            $this->run($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-after-frameset-insertion-mode
     */
    private function afterAfterFramesetInsertionMode(Token $token): void
    {
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof CommentToken) {
            // Insert a comment as the last child of the Document object.
            $this->insertComment($token, [$this->document, 'beforeend']);
        } elseif (
            $token instanceof DoctypeToken
            || (
                $token instanceof CharacterToken
                && (
                    ($data = $token->data) === "\x09"
                    || $data === "\x0A"
                    || $data === "\x0C"
                    || $data === "\x0D"
                    || $data === "\x20"
                )
            )
            || ($token instanceof StartTagToken && $tagName === 'html')
        ) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($token);
        } elseif ($token instanceof EOFToken) {
            // Stop parsing.
            $this->stopParsing();
        } elseif ($token instanceof StartTagToken && $tagName === 'noframes') {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($token);
        } else {
            // Parse error.
            // Ignore the token.
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inforeign
     */
    private function inForeignContent(Token $token): void
    {
        $fontTokenHasAttribute = false;
        $tagName = $token instanceof TagToken ? $token->tagName : '';

        if ($token instanceof StartTagToken && $token->tagName === 'font') {
            foreach ($token->attributes as $attr) {
                if ($attr->name === 'color' || $attr->name === 'face' || $attr->name === 'size') {
                    $fontTokenHasAttribute = true;
                }
            }
        }

        if ($token instanceof CharacterToken && $token->data === "\x00") {
            // Parse error.
            // Insert a U+FFFD REPLACEMENT CHARACTER character.
            $this->insertCharacter("\u{FFFD}");
        } elseif (
            $token instanceof CharacterToken
            && (
                ($data = $token->data) === "\x09"
                || $data === "\x0A"
                || $data === "\x0C"
                || $data === "\x0D"
                || $data === "\x20"
            )
        ) {
            // Insert the token's character.
            $this->insertCharacter($token);
        } elseif ($token instanceof CharacterToken) {
            // Insert the token's character.
            $this->insertCharacter($token);

            // Set the frameset-ok flag to "not ok".
            $this->framesetOk = 'not ok';
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif (
            $token instanceof StartTagToken
            && (
                preg_match(
                    '/^(b|big|blockquote|body|br|center|code|dd|div|dl|dt|em|'
                    . 'embed|h[1-6]|head|hr|i|img|li|listing|menu|meta|nobr|ol|p|'
                    . 'pre|ruby|s|small|span|strong|strike|sub|sup|table|tt|u|ul|'
                    . 'var)$/',
                    $tagName
                )
                || (
                    $token instanceof StartTagToken
                    && $tagName === 'font'
                    && $fontTokenHasAttribute
                )
            )
        ) {
            // Parse error.
            // If the parser was originally created for the HTML fragment
            // parsing algorithm, then act as described in the "any other start
            // tag" entry below. (fragment case)
            if ($this->isFragmentCase) {
                $this->inForeignContentAnyOtherStartTag($token);

                return;
            }

            // Pop an element from the stack of open elements, and then keep
            // popping more elements from the stack of open elements until the
            // current node is a MathML text integration point, an HTML
            // integration point, or an element in the HTML namespace.
            while (!$this->openElements->isEmpty()) {
                $this->openElements->pop();
                $currentNode = $this->openElements->bottom();

                if (
                    $this->isMathMLTextIntegrationPoint($currentNode)
                    || $this->isHTMLIntegrationPoint($currentNode)
                    || (
                        $currentNode instanceof Element
                        && $currentNode->namespaceURI === Namespaces::HTML
                    )
                ) {
                    break;
                }
            }

            // Then, reprocess the token.
            $this->run($token);
        } elseif ($token instanceof StartTagToken) {
            $this->inForeignContentAnyOtherStartTag($token);
        } elseif (
            $token instanceof EndTagToken
            && $tagName === 'script'
            && $this->openElements->bottom() instanceof SVGScriptElement
        ) {
            $this->inForeignContentScriptEndTag($token);
        } elseif ($token instanceof EndTagToken) {
            // 1. Initialize node to be the current node (the bottommost node of the stack).
            $node = $this->openElements->bottom();

            // 2. If node's tag name, converted to ASCII lowercase, is not the same as the tag name
            // of the token, then this is a parse error.
            if (Utils::toASCIILowercase($node->tagName) !== $tagName) {
                // Parse error.
            }

            $iter = $this->openElements->getIterator();
            $iter->rewind();

            // 3. Loop: If node is the topmost element in the stack of open elements, then return.
            // (fragment case)
            while ($iter->valid()) {
                if ($node === $this->openElements->top()) {
                    return;
                }

                // 4. If node's tag name, converted to ASCII lowercase, is the same as the tag name
                // of the token, pop elements from the stack of open elements until node has been
                // popped from the stack, and then return.
                if (Utils::toASCIILowercase($node->tagName) === $tagName) {
                    while (!$this->openElements->isEmpty()) {
                        $iter->next();

                        if ($this->openElements->pop() === $node) {
                            return;
                        }
                    }

                    $iter = $this->openElements->getIterator();
                    $iter->rewind();
                } else {
                    $iter->next();
                }

                // 5. Set node to the previous entry in the stack of open elements.
                $node = $iter->current();

                // 6. If node is not an element in the HTML namespace, return to the step labeled loop.
                if ($node instanceof Element && $node->namespaceURI === Namespaces::HTML) {
                    break;
                }
            }

            // 7. Otherwise, process the token according to the rules given in the section
            // corresponding to the current insertion mode in HTML content.
            $this->processWithCurrentInsertionMode($token);
        }
    }

    /**
     * The in foreign content's "any other start tag" steps.
     */
    private function inForeignContentAnyOtherStartTag(StartTagToken $token): void
    {
        $adjustedCurrentNode = $this->getAdjustedCurrentNode();
        $isElementInSVGNamespace = false;
        $namespace = $adjustedCurrentNode->namespaceURI;

        // If the adjusted current node is an element in the MathML
        // namespace, adjust MathML attributes for the token. (This
        // fixes the case of MathML attributes that are not all
        // lowercase.)
        if ($namespace === Namespaces::MATHML) {
            $this->adjustMathMLAttributes($token);
        }

        $isElementInSVGNamespace = $namespace === Namespaces::SVG;

        // If the adjusted current node is an element in the SVG namespace,
        // and the token's tag name is one of the ones in the first column
        // of the following table, change the tag name to the name given in
        // the corresponding cell in the second column. (This fixes the case
        // of SVG elements that are not all lowercase.)
        if ($isElementInSVGNamespace && isset(self::SVG_ELEMENTS[$token->tagName])) {
            $token->tagName = self::SVG_ELEMENTS[$token->tagName];
        }

        // If the adjusted current node is an element in the SVG namespace,
        // adjust SVG attributes for the token. (This fixes the case of SVG
        // attributes that are not all lowercase.)
        if ($isElementInSVGNamespace) {
            $this->adjustSVGAttributes($token);
        }

        // Adjust foreign attributes for the token. (This fixes the use of
        // namespaced attributes, in particular XLink in SVG.)
        $this->adjustForeignAttributes($token);

        // Insert a foreign element for the token, in the same namespace as the
        // adjusted current node.
        $this->insertForeignElement($token, $namespace);

        // If the token has its self-closing flag set...
        if ($token->isSelfClosing()) {
            // If the token's tag name is "script", and the new current node is
            // in the SVG namespace...
            if (
                $token->tagName === 'script'
                && $this->openElements->bottom()->namespaceURI === Namespaces::SVG
            ) {
                // Acknowledge the token's self-closing flag, and then act as
                // described in the steps for a "script" end tag below.
                $token->acknowledge();
                $this->inForeignContentScriptEndTag($token);
            } else {
                // Pop the current node off the stack of open elements and
                // acknowledge the token's self-closing flag.
                $this->openElements->pop();
                $token->acknowledge();
            }
        }
    }

    /**
     * The in foreign content's "script end tag" steps.
     */
    private function inForeignContentScriptEndTag(TagToken $token): void
    {
        // Pop the current node off the stack of open elements.
        $this->openElements->pop();

        // TODO: More stuff that will probably never be fully supported.
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#tree-construction-dispatcher
     */
    public function run(Token $token): void
    {
        $useCurrentInsertionMode = false;

        while (true) {
            if ($this->openElements->isEmpty()) {
                $useCurrentInsertionMode = true;

                break;
            }

            $adjustedCurrentNode = $this->getAdjustedCurrentNode();

            if (
                $adjustedCurrentNode instanceof Element
                && $adjustedCurrentNode->namespaceURI === Namespaces::HTML
            ) {
                $useCurrentInsertionMode = true;

                break;
            }

            if ($this->isMathMLTextIntegrationPoint($adjustedCurrentNode)) {
                if (
                    $token instanceof StartTagToken
                    && $token->tagName !== 'mglyph'
                    && $token->tagName !== 'malignmark'
                ) {
                    $useCurrentInsertionMode = true;

                    break;
                } elseif ($token instanceof CharacterToken) {
                    $useCurrentInsertionMode = true;

                    break;
                }
            }

            if (
                $adjustedCurrentNode instanceof Element
                && $adjustedCurrentNode->namespaceURI === Namespaces::MATHML
                && $adjustedCurrentNode->localName === 'annotaion-xml'
                && $token instanceof StartTagToken
                && $token->tagName === 'svg'
            ) {
                $useCurrentInsertionMode = true;

                break;
            }

            if (
                $this->isHTMLIntegrationPoint($adjustedCurrentNode)
                && ($token instanceof StartTagToken || $token instanceof CharacterToken)
            ) {
                $useCurrentInsertionMode = true;

                break;
            }

            if ($token instanceof EOFToken) {
                $useCurrentInsertionMode = true;

                break;
            }

            break;
        }

        if (!$useCurrentInsertionMode) {
            $this->inForeignContent($token);

            return;
        }

        $this->processWithCurrentInsertionMode($token);
    }

    public function processWithCurrentInsertionMode(Token $token): void
    {
        switch ($this->state->insertionMode) {
            case ParserInsertionMode::INITIAL:
                $this->initialInsertionMode($token);

                break;

            case ParserInsertionMode::BEFORE_HTML:
                $this->beforeHTMLInsertionMode($token);

                break;

            case ParserInsertionMode::BEFORE_HEAD:
                $this->beforeHeadInsertionMode($token);

                break;

            case ParserInsertionMode::IN_HEAD:
                $this->inHeadInsertionMode($token);

                break;

            case ParserInsertionMode::IN_HEAD_NOSCRIPT:
                $this->inHeadNoScriptInsertionMode($token);

                break;

            case ParserInsertionMode::AFTER_HEAD:
                $this->afterHeadInsertionMode($token);

                break;

            case ParserInsertionMode::IN_BODY:
                $this->inBodyInsertionMode($token);

                break;

            case ParserInsertionMode::TEXT:
                $this->inTextInsertionMode($token);

                break;

            case ParserInsertionMode::IN_TABLE:
                $this->inTableInsertionMode($token);

                break;

            case ParserInsertionMode::IN_TABLE_TEXT:
                $this->inTableTextInsertionMode($token);

                break;

            case ParserInsertionMode::IN_CAPTION:
                $this->inCaptionInsertionMode($token);

                break;

            case ParserInsertionMode::IN_COLUMN_GROUP:
                $this->inColumnGroupInsertionMode($token);

                break;

            case ParserInsertionMode::IN_TABLE_BODY:
                $this->inTableBodyInsertionMode($token);

                break;

            case ParserInsertionMode::IN_ROW:
                $this->inRowInsertionMode($token);

                break;

            case ParserInsertionMode::IN_CELL:
                $this->inCellInsertionMode($token);

                break;

            case ParserInsertionMode::IN_SELECT:
                $this->inSelectInsertionMode($token);

                break;

            case ParserInsertionMode::IN_SELECT_IN_TABLE:
                $this->inSelectInTableInsertionMode($token);

                break;

            case ParserInsertionMode::IN_TEMPLATE:
                $this->inTemplateInsertionMode($token);

                break;

            case ParserInsertionMode::AFTER_BODY:
                $this->afterBodyInsertionMode($token);

                break;

            case ParserInsertionMode::IN_FRAMESET:
                $this->inFramesetInsertionMode($token);

                break;

            case ParserInsertionMode::AFTER_FRAMESET:
                $this->afterFramesetInsertionMode($token);

                break;

            case ParserInsertionMode::AFTER_AFTER_BODY:
                $this->afterAfterBodyInsertionMode($token);

                break;

            case ParserInsertionMode::AFTER_AFTER_FRAMESET:
                $this->afterAfterFramesetInsertionMode($token);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adjust-foreign-attributes
     */
    private function adjustForeignAttributes(TagToken $token): void
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
                $attr->prefix = $matches[0][1];
                $attr->name = $matches[0][2];
                $attr->namespace = Namespaces::XLINK;
            } elseif (preg_match('/^(xml):(lang|space)$/', $name, $matches)) {
                $attr->prefix = $matches[0][1];
                $attr->name = $matches[0][2];
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
    private function adjustMathMLAttributes(TagToken $token): void
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
    private function adjustSVGAttributes(TagToken $token): void
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
    private function createElementForToken(
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
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generate-all-implied-end-tags-thoroughly
     */
    private function generateAllImpliedEndTagsThoroughly(): void
    {
        $pattern = '/^(caption|colgroup|dd|dt|li|optgroup|option|p|rb|rp|rt';
        $pattern .= '|rtc|tbody|td|tfoot|th|thead|tr)$/';

        foreach ($this->openElements as $currentNode) {
            if (
                !$currentNode instanceof HTMLElement
                || !preg_match($pattern, $currentNode->localName)
            ) {
                break;
            }

            $this->openElements->pop();
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generate-implied-end-tags
     */
    private function generateImpliedEndTags(string $excluded = ''): void
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

        foreach ($this->openElements as $currentNode) {
            if (!isset($tags[$currentNode->localName])) {
                break;
            }

            $this->openElements->pop();
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
     * @return array{0: \Rowbot\DOM\Node, 1: string} The first index contains the node where another node will be in the
     *                                               first index that, the node to be inserted will be inserted.
     */
    private function getAppropriatePlaceForInsertingNode(Node $overrideTarget = null): array
    {
        // If there was an override target specified, then let target be the
        // override target. Otherwise, let target be the current node.
        $target = $overrideTarget ?? $this->openElements->bottom();

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

            foreach ($this->openElements as $key => $element) {
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

            while (true) {
                if ($lastTemplate && (!$lastTable || $lastTemplateIndex > $lastTableIndex)) {
                    $adjustedInsertionLocation = [$lastTemplate->content, 'beforeend'];

                    break;
                }

                if ($lastTable === null) {
                    // Fragment case
                    $adjustedInsertionLocation = [$this->openElements[0], 'beforeend'];

                    break;
                }

                if ($lastTable->parentNode) {
                    $adjustedInsertionLocation = [$lastTable, 'beforebegin'];

                    break;
                }

                $previousElement = $this->openElements[$lastTableIndex - 1];
                $adjustedInsertionLocation = [$previousElement, 'beforeend'];

                break;
            }
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
    private function insertCharacter($data): void
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
            $this->textBuilder->append($data);

            return;
        }

        $this->textBuilder->flushText();
        $node = new Text($adjustedInsertionLocation[0]->getNodeDocument());
        $this->textBuilder->setNode($node);
        $this->textBuilder->append($data);
        $this->insertNode($node, $adjustedInsertionLocation);
    }

    /**
     * Inserts a comment node in to the document while processing a comment
     * token.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#insert-a-comment
     *
     * @param \Rowbot\DOM\Parser\Token\CommentToken      $token    The comment token being processed.
     * @param array{0: \Rowbot\DOM\Node, 1: string}|null $position (optional) The position where the comment should be
     *                                                             inserted.
     */
    private function insertComment(CommentToken $token, array $position = null): void
    {
        // Let data be the data given in the comment token being processed.
        $data = $token->data;

        // If position was specified, then let the adjusted insertion location
        // be position. Otherwise, let adjusted insertion location be the
        // appropriate place for inserting a node.
        if ($position !== null) {
            $adjustedInsertionLocation = $position;
        } else {
            $adjustedInsertionLocation = $this->getAppropriatePlaceForInsertingNode();
        }

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
    private function insertForeignElement(TagToken $token, ?string $namespace): Element
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
        $this->openElements->push($element);
        $this->tokenRepository->attach($element, $token);

        // Return the newly created element.
        return $element;
    }

    /**
     * Inserts a node based at a specific location. It follows similar rules to
     * Element's insertAdjacentHTML method.
     *
     * @param \Rowbot\DOM\Node                      $node     The node that is being inserted in to the document.
     * @param array{0: \Rowbot\DOM\Node, 1: string} $position The position at which the node is to be inserted.
     */
    private function insertNode(Node $node, array $position): void
    {
        $relativeNode = $position[0];
        $position = $position[1];

        if ($position === 'beforebegin') {
            $relativeNode->parentNode->insertNode($node, $relativeNode);
        } elseif ($position === 'afterbegin') {
            $relativeNode->insertNode($node, $relativeNode->firstChild);
        } elseif ($position === 'beforeend') {
            $relativeNode->appendChild($node);
        } elseif ($position === 'afterend') {
            $relativeNode->parentNode->insertNode($node, $relativeNode->nextSibling);
        }
    }

    private function isHTMLElementWithName(Node $node, string $localName): bool
    {
        return $node instanceof Element
            && $node->namespaceURI === Namespaces::HTML
            && $node->localName === $localName;
    }

    /**
     * A node is an HTML integration point if it is one of the following:
     *
     *     - A MathML annotation-xml element whose start tag token had an
     *     attribute with the name "encoding" whose value was an ASCII
     *     case-insensitive match for the string "text/html".
     *
     *     - A MathML annotation-xml element whose start tag token had an
     *     attribute with the name "encoding" whose value was an ASCII
     *     case-insensitive match for the string "application/xhtml+xml".
     *
     *     - An SVG foreignObject element.
     *
     *     - An SVG desc element.
     *
     *     - An SVG title element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#html-integration-point
     */
    private function isHTMLIntegrationPoint(Node $node): bool
    {
        if (!$node instanceof Element) {
            return false;
        }

        if ($node->namespaceURI === Namespaces::MATHML) {
            if ($node->localName !== 'annotaion-xml') {
                return false;
            }

            foreach ($this->tokenRepository[$node]->attributes as $attr) {
                if ($attr->name === 'encoding') {
                    $value = Utils::toASCIILowercase($attr->value);

                    if ($value === 'text/html' || $value === 'application/xhtml+xml') {
                        return true;
                    }
                }
            }
        } elseif (
            $node instanceof SVGForeignObjectElement
            || $node instanceof SVGDescElement
            || $node instanceof SVGTitleElement
        ) {
            return true;
        }

        return false;
    }

    /**
     * A node is a MathML text integration point if it is one of the following
     * elements:
     *     - A MathML mi element.
     *     - A MathML mo element.
     *     - A MathML mn element.
     *     - A MathML ms element.
     *     - A MathML mtext element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#mathml-text-integration-point
     */
    private function isMathMLTextIntegrationPoint(Node $node): bool
    {
        if ($node instanceof Element && $node->namespaceURI === Namespaces::MATHML) {
            $localName = $node->localName;

            return $localName === 'mi'
                || $localName === 'mo'
                || $localName === 'mn'
                || $localName === 'ms'
                || $localName === 'mtext';
        }

        return false;
    }

    /**
     * Returns whether or not the element has special parsing rules.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#special
     */
    private function isSpecialNode(Node $node): bool
    {
        if (!$node instanceof Element) {
            return false;
        }

        $namespace = $node->namespaceURI;

        if ($namespace === Namespaces::HTML) {
            $localName = $node->localName;

            return $localName === 'address'
                || $localName === 'applet'
                || $localName === 'area'
                || $localName === 'article'
                || $localName === 'aside'
                || $localName === 'base'
                || $localName === 'basefont'
                || $localName === 'bgsound'
                || $localName === 'blockquote'
                || $localName === 'body'
                || $localName === 'br'
                || $localName === 'button'
                || $localName === 'caption'
                || $localName === 'center'
                || $localName === 'col'
                || $localName === 'colgroup'
                || $localName === 'dd'
                || $localName === 'details'
                || $localName === 'dir'
                || $localName === 'div'
                || $localName === 'dl'
                || $localName === 'dt'
                || $localName === 'embed'
                || $localName === 'fieldset'
                || $localName === 'figcaption'
                || $localName === 'figure'
                || $localName === 'footer'
                || $localName === 'form'
                || $localName === 'frame'
                || $localName === 'frameset'
                || $localName === 'h1'
                || $localName === 'h2'
                || $localName === 'h3'
                || $localName === 'h4'
                || $localName === 'h5'
                || $localName === 'h6'
                || $localName === 'head'
                || $localName === 'header'
                || $localName === 'hgroup'
                || $localName === 'hr'
                || $localName === 'html'
                || $localName === 'iframe'
                || $localName === 'img'
                || $localName === 'input'
                || $localName === 'keygen'
                || $localName === 'li'
                || $localName === 'link'
                || $localName === 'listing'
                || $localName === 'main'
                || $localName === 'marquee'
                || $localName === 'menu'
                || $localName === 'meta'
                || $localName === 'nav'
                || $localName === 'noembed'
                || $localName === 'noframes'
                || $localName === 'noscript'
                || $localName === 'object'
                || $localName === 'ol'
                || $localName === 'p'
                || $localName === 'param'
                || $localName === 'plaintext'
                || $localName === 'pre'
                || $localName === 'script'
                || $localName === 'section'
                || $localName === 'select'
                || $localName === 'source'
                || $localName === 'style'
                || $localName === 'summary'
                || $localName === 'table'
                || $localName === 'tbody'
                || $localName === 'td'
                || $localName === 'template'
                || $localName === 'textarea'
                || $localName === 'tfoot'
                || $localName === 'th'
                || $localName === 'thead'
                || $localName === 'title'
                || $localName === 'tr'
                || $localName === 'track'
                || $localName === 'ul'
                || $localName === 'wbr'
                || $localName === 'xmp';
        } elseif ($namespace === Namespaces::MATHML) {
            $localName = $node->localName;

            return $localName === 'mi'
                || $localName === 'mo'
                || $localName === 'mn'
                || $localName === 'ms'
                || $localName === 'mtext'
                || $localName === 'annotation-xml';
        } elseif (
            $node instanceof SVGForeignObjectElement
            || $node instanceof SVGDescElement
            || $node instanceof SVGTitleElement
        ) {
            return true;
        }

        return false;
    }

    /**
     * This algorithm is always invoked in response to a start tag token.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generic-raw-text-element-parsing-algorithm
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generic-rcdata-element-parsing-algorithm
     */
    private function parseGenericTextElement(StartTagToken $token, int $algorithm): void
    {
        // Insert an HTML element for the token.
        $this->insertForeignElement($token, Namespaces::HTML);

        // If the algorithm that was invoked is the generic raw text element
        // parsing algorithm, switch the tokenizer to the RAWTEXT state;
        // otherwise the algorithm invoked was the generic RCDATA element
        // parsing algorithm, switch the tokenizer to the RCDATA state.
        if ($algorithm === self::RAW_TEXT_ELEMENT_ALGORITHM) {
            $this->state->tokenizerState = TokenizerState::RAWTEXT;
        } else {
            $this->state->tokenizerState = TokenizerState::RCDATA;
        }

        // Let the original insertion mode be the current insertion mode.
        $this->originalInsertionMode = $this->state->insertionMode;

        // Then, switch the insertion mode to "text".
        $this->state->insertionMode = ParserInsertionMode::TEXT;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#reconstruct-the-active-formatting-elements
     */
    private function reconstructActiveFormattingElements(): void
    {
        // If there are no entries in the list of active formatting elements,
        // then there is nothing to reconstruct; stop this algorithm.
        if ($this->activeFormattingElements->isEmpty()) {
            return;
        }

        // If the last (most recently added) entry in the list of active
        // formatting elements is a marker, or if it is an element that is in
        // the stack of open elements, then there is nothing to reconstruct;
        // stop this algorithm.
        $entry = $this->activeFormattingElements->top();

        if ($entry instanceof Marker || $this->openElements->contains($entry)) {
            return;
        }

        $cursor = count($this->activeFormattingElements) - 1;

        // If there are no entries before entry in the list of active formatting
        // elements, then jump to the step labeled create.
        Rewind:

        if ($cursor === 0) {
            goto Create;
        }

        // Let entry be the entry one earlier than entry in the list of active
        // formatting elements.
        $entry = $this->activeFormattingElements[--$cursor];

        // If entry is neither a marker nor an element that is also in the stack
        // of open elements, go to the step labeled rewind.
        if (!$entry instanceof Marker && !$this->openElements->contains($entry)) {
            goto Rewind;
        }

        Advance:
        // Let entry be the element one later than entry in the list of active
        // formatting elements.
        $entry = $this->activeFormattingElements[++$cursor];

        Create:
        // Insert an HTML element for the token for which the element entry was
        // created, to obtain new element.
        $newElement = $this->insertForeignElement($this->tokenRepository[$entry], Namespaces::HTML);

        // Replace the entry for entry in the list with an entry for new
        // element.
        $this->activeFormattingElements->replace($newElement, $entry);

        // If the entry for new element in the list of active formatting
        // elements is not the last entry in the list, return to the step
        // labeled advance.
        if ($newElement !== $this->activeFormattingElements->top()) {
            goto Advance;
        }
    }

    /**
     * Performs an ASCII case-insensitive compare of a DOCTYPE token's public
     * or system identifier to see if the identifier begins with one of the
     * fragment strings.
        *
     * @param list<string> $fragments
     */
    private function identifierBeginsWith(?string $identifier, array $fragments): bool
    {
        // If the given identifier is null, this means that the DOCTYPE token's
        // identifier was missing and it cannot possibly match anything in the
        // list.
        if ($identifier === null) {
            return false;
        }

        // Make the identifier ASCII lowercased for comparison.
        $identifier = Utils::toASCIILowercase($identifier);

        foreach ($fragments as $identifierFragment) {
            if (
                mb_strpos(
                    $identifier,
                    Utils::toASCIILowercase($identifierFragment),
                    0,
                    'utf-8'
                ) === 0
            ) {
                return true;
            }
        }

        return false;
    }
}
