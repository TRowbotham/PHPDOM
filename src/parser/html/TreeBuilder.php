<?php
namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentMode;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLButtonElement;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLFieldSetElement;
use Rowbot\DOM\Element\HTML\HTMLFormElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHeadingElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLImageElement;
use Rowbot\DOM\Element\HTML\HTMLInputElement;
use Rowbot\DOM\Element\HTML\HTMLKeygenElement;
use Rowbot\DOM\Element\HTML\HTMLLIElement;
use Rowbot\DOM\Element\HTML\HTMLObjectElement;
use Rowbot\DOM\Element\HTML\HTMLOptGroupElement;
use Rowbot\DOM\Element\HTML\HTMLOptionElement;
use Rowbot\DOM\Element\HTML\HTMLOutputElement;
use Rowbot\DOM\Element\HTML\HTMLParagraphElement;
use Rowbot\DOM\Element\HTML\HTMLScriptElement;
use Rowbot\DOM\Element\HTML\HTMLSelectElement;
use Rowbot\DOM\Element\HTML\HTMLTableCaptionElement;
use Rowbot\DOM\Element\HTML\HTMLTableCellElement;
use Rowbot\DOM\Element\HTML\HTMLTableColElement;
use Rowbot\DOM\Element\HTML\HTMLTableDataElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableHeaderCellElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Element\HTML\HTMLTextAreaElement;
use Rowbot\DOM\Encoding\EncodingUtils;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\Bookmark;
use Rowbot\DOM\Parser\Marker;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Parser\Token\Token;
use Rowbot\DOM\Text;
use Rowbot\DOM\Utils;

class TreeBuilder
{
    const CONFIDENCE_TENTATIVE = 1;

    const RAW_TEXT_ELEMENT_ALGORITHM = 1;
    const RCDATA_ELEMENT_ALGORITHM   = 2;

    const REGEX_CHARACTERS = '/^[\x{0009}\x{000A}\x{000C}\x{000D}\x{0020}]$/';

    const SVG_ATTRIBUTES = [
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
        'specularexponent'    => 'specularexponent',
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
        'zoomandpan'          => 'zoomAndPan'
    ];

    const SVG_ELEMENTS = [
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
        'textpath'            => 'textPath'
    ];

    private $mFosterParenting;
    private $mFramesetOk;
    private $mParser;
    private $mPendingTableCharacterTokens;
    private $mActiveFormattingElements;
    private $mOpenElements;
    private $mTemplateInsertionModes;

    public function __construct(Document $aDocument, HTMLParser $aParser)
    {
        $this->mDocument = $aDocument;
        $this->mFosterParenting = false;
        $this->mFramesetOk = 'ok';
        $this->mParser = $aParser;
        $this->mOpenElements = $aParser->getOpenElementStack();
        $this->mActiveFormattingElements =
            $aParser->getActiveFormattingElementStack();
        $this->mTemplateInsertionModes =
            $aParser->getTemplateInsertionModeStack();
        $this->mPendingTableCharacterTokens = null;
        $this->mTokenRepository = new \SplObjectStorage();
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-initial-insertion-mode
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function initialInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();

        if ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Ignore the token.
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            $this->insertComment($aToken, [$this->mDocument, 'beforeend']);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            $publicId = $aToken->publicIdentifier;
            $systemId = $aToken->systemIdentifier;
            $name = $aToken->name;

            if ($name !== 'html' || $publicId !== null ||
                ($systemId !== null && $systemId !== 'about:legacy-compat')
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
                ($name ?: ''),
                ($publicId ?: ''),
                ($systemId ?: '')
            );
            $doctype->setNodeDocument($this->mDocument);
            $this->mDocument->appendChild($doctype);

            if (!$this->mDocument->isIframeSrcdoc() &&
                ($aToken->getQuirksMode() === 'on' ||
                $name !== 'html' ||
                strcasecmp($publicId, '-//W3O//DTD W3 HTML Strict 3.0//EN//') === 0 ||
                strcasecmp($publicId, '-/W3C/DTD HTML 4.0 Transitional/EN') === 0 ||
                strcasecmp($publicId, 'HTML') === 0 ||
                strcasecmp($systemId, 'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd') === 0 ||
                stripos($publicId, '+//Silmaril//dtd html Pro v0r11 19970101//') === 0 ||
                stripos($publicId, '-//AS//DTD HTML 3.0 asWedit + extensions//') === 0 ||
                stripos($publicId, '-//AdvaSoft Ltd//DTD HTML 3.0 asWedit + extensions//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 2.0 Level 1//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 2.0 Level 2//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 2.0 Strict Level 1//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 2.0 Strict Level 2//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 2.0 Strict//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 2.0//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 2.1E//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 3.0//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 3.2 Final//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 3.2//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML 3//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Level 0//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Level 1//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Level 2//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Level 3//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Strict Level 0//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Strict Level 1//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Strict Level 2//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Strict Level 3//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML Strict//') === 0 ||
                stripos($publicId, '-//IETF//DTD HTML//') === 0 ||
                stripos($publicId, '-//Metrius//DTD Metrius Presentational//') === 0 ||
                stripos($publicId, '-//Microsoft//DTD Internet Explorer 2.0 HTML Strict//') === 0 ||
                stripos($publicId, '-//Microsoft//DTD Internet Explorer 2.0 HTML//') === 0 ||
                stripos($publicId, '-//Microsoft//DTD Internet Explorer 2.0 Tables//') === 0 ||
                stripos($publicId, '-//Microsoft//DTD Internet Explorer 3.0 HTML Strict//') === 0 ||
                stripos($publicId, '-//Microsoft//DTD Internet Explorer 3.0 HTML//') === 0 ||
                stripos($publicId, '-//Microsoft//DTD Internet Explorer 3.0 Tables//') === 0 ||
                stripos($publicId, '-//Netscape Comm. Corp.//DTD HTML//') === 0 ||
                stripos($publicId, '-//Netscape Comm. Corp.//DTD Strict HTML//') === 0 ||
                stripos($publicId, '-//O\'Reilly and Associates//DTD HTML 2.0//') === 0 ||
                stripos($publicId, '-//O\'Reilly and Associates//DTD HTML Extended 1.0//') === 0 ||
                stripos($publicId, '-//O\'Reilly and Associates//DTD HTML Extended Relaxed 1.0//') === 0 ||
                stripos($publicId, '-//SQ//DTD HTML 2.0 HoTMetaL + extensions//') === 0 ||
                stripos($publicId, '-//SoftQuad Software//DTD HoTMetaL PRO 6.0::19990601::extensions to HTML 4.0//') === 0 ||
                stripos($publicId, '-//SoftQuad//DTD HoTMetaL PRO 4.0::19971010::extensions to HTML 4.0//') === 0 ||
                stripos($publicId, '-//Spyglass//DTD HTML 2.0 Extended//') === 0 ||
                stripos($publicId, '-//Sun Microsystems Corp.//DTD HotJava HTML//') === 0 ||
                stripos($publicId, '-//Sun Microsystems Corp.//DTD HotJava Strict HTML//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML 3 1995-03-24//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML 3.2 Draft//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML 3.2 Final//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML 3.2//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML 3.2S Draft//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML 4.0 Frameset//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML 4.0 Transitional//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML Experimental 19960712//') === 0 ||
                stripos($publicId, '-//W3C//DTD HTML Experimental 970421//') === 0 ||
                stripos($publicId, '-//W3C//DTD W3 HTML//') === 0 ||
                stripos($publicId, '-//W3O//DTD W3 HTML 3.0//') === 0 ||
                stripos($publicId, '-//WebTechs//DTD Mozilla HTML 2.0//') === 0 ||
                stripos($publicId, '-//WebTechs//DTD Mozilla HTML//') === 0 ||
                ($systemId === null && stripos($publicId, '-//W3C//DTD HTML 4.01 Frameset//') === 0) ||
                ($systemId === null && stripos($publicId, '-//W3C//DTD HTML 4.01 Transitional//') === 0))
            ) {
                $this->mDocument->setMode(DocumentMode::QUIRKS);
            } elseif (!$this->mDocument->isIframeSrcdoc() &&
                (stripos(
                    $publicId,
                    '-//W3C//DTD XHTML 1.0 Frameset//'
                ) === 0 ||
                stripos(
                    $publicId,
                    '-//W3C//DTD XHTML 1.0 Transitional//'
                ) === 0 ||
                ($systemId !== null && stripos(
                    $publicId,
                    '-//W3C//DTD HTML 4.01 Frameset//'
                ) === 0) ||
                ($systemId !== null && stripos(
                    $publicId,
                    '-//W3C//DTD HTML 4.01 Transitional//'
                ) === 0))
            ) {
                $this->mDocument->setMode(DocumentMode::LIMITED_QUIRKS);
            }

            // Then, switch the insertion mode to "before html".
            $this->mParser->setInsertionMode(ParserInsertionMode::BEFORE_HTML);
        } else {
            // If the document is not an iframe srcdoc document, then this
            // is a parse error; set the Document to quirks mode.
            if (!$this->mDocument->isIframeSrcdoc()) {
                // Parse error.
                $this->mDocument->setMode(DocumentMode::QUIRKS);
            }

            // In any case, switch the insertion mode to "before html", then
            // reprocess the token.
            $this->mParser->setInsertionMode(ParserInsertionMode::BEFORE_HTML);
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-before-html-insertion-mode
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function beforeHTMLInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment as the last child of the Document object.
            $this->insertComment($aToken, [$this->mDocument, 'beforeend']);
        } elseif ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html') {
            // Create an element for the token in the HTML namespace, with the
            // Document as the intended parent. Append it to the Document
            // object. Put this element in the stack of open elements.
            $node = $this->createElementForToken(
                $aToken,
                Namespaces::HTML,
                $this->mDocument
            );
            $this->mDocument->appendChild($node);
            $this->mOpenElements->push($node);

            // TODO: If the Document is being loaded as part of navigation of a
            // browsing context, run these steps:

            // Switch the insertion mode to "before head".
            $this->mParser->setInsertionMode(ParserInsertionMode::BEFORE_HEAD);
        } elseif ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'head' ||
            $tagName === 'body' || $tagName === 'html' || $tagName === 'br')
        ) {
            // Act as described in the "anything else" entry below.

            // Create an html element whose node document is the Document
            // object. Append it to the Document object. Put this element in
            // the stack of open elements.
            $node = ElementFactory::create(
                $this->mDocument,
                'html',
                Namespaces::HTML
            );
            $this->mDocument->appendChild($node);
            $this->mOpenElements->push($node);

            // TODO: If the Document is being loaded as part of navigation of a
            // browsing context, then: run the application cache selection
            // algorithm with no manifest, passing it the Document object.

            // Switch the insertion mode to "before head", then reprocess the
            // token.
            $this->mParser->setInsertionMode(ParserInsertionMode::BEFORE_HEAD);
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN) {
            // Parse error.
            // Ignore the token.
        } else {
            // Create an html element whose node document is the Document
            // object. Append it to the Document object. Put this element in
            // the stack of open elements.
            $node = ElementFactory::create(
                $this->mDocument,
                'html',
                Namespaces::HTML
            );
            $this->mDocument->appendChild($node);
            $this->mOpenElements->push($node);

            // TODO: If the Document is being loaded as part of navigation of a
            // browsing context, then: run the application cache selection
            // algorithm with no manifest, passing it the Document object.

            // Switch the insertion mode to "before head", then reprocess the
            // token.
            $this->mParser->setInsertionMode(ParserInsertionMode::BEFORE_HEAD);
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-before-head-insertion-mode
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function beforeHeadInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Ignore the token.
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'head') {
            // Insert an HTML element for the token.
            $node = $this->insertForeignElement($aToken, Namespaces::HTML);

            // Set the head element pointer to the newly created head element.
            $this->mParser->setHeadElementPointer($node);

            // Switch the insertion mode to "in head".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_HEAD);
        } elseif ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'head' ||
            $tagName === 'body' || $tagName === 'html' || $tagName === 'br')
        ) {
            // Act as described in the "anything else" entry below.

            // Insert an HTML element for a "head" start tag token with no
            // attributes.
            $node = $this->insertForeignElement(
                new StartTagToken('head'),
                Namespaces::HTML
            );

            // Set the head element pointer to the newly created head element.
            $this->mParser->setHeadElementPointer($node);

            // Switch the insertion mode to "in head".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_HEAD);

            // Reprocess the current token.
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN) {
            // Parse error.
            // Ignore the token.
        } else {
            // Insert an HTML element for a "head" start tag token with no
            // attributes.
            $node = $this->insertForeignElement(
                new StartTagToken('head'),
                Namespaces::HTML
            );

            // Set the head element pointer to the newly created head element.
            $this->mParser->setHeadElementPointer($node);

            // Switch the insertion mode to "in head".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_HEAD);

            // Reprocess the current token.
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inhead
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inHeadInsertionMode(Token $aToken)
    {
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($aToken instanceof CharacterToken &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Insert the character.
            $this->insertCharacter($aToken);
        } elseif ($aToken instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($aToken instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($aToken instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($aToken instanceof StartTagToken &&
            ($tagName === 'base' || $tagName === 'basefont' ||
                $tagName === 'bgsound' || $tagName === 'link')
        ) {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }
        } elseif ($aToken instanceof StartTagToken && $tagName === 'meta') {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $node = $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
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
            $encoding = EncodingUtils::getEncoding(
                $node->getAttribute('charset')
            );

            if ($encoding !== false &&
                $this->mParser->getEncodingConfidence() ==
                self::CONFIDENCE_TENTATIVE
            ) {
                // TODO: change the encoding to the resulting encoding
            } elseif ($node->hasAttribute('http-equiv') &&
                strcasecmp(
                    $node->getAttribute('http-equiv'),
                    'Content-Type'
                ) === 0 && $node->hasAttribute('content')
            ) {
                // TODO
            }
        } elseif ($aToken instanceof StartTagToken &&
            $tagName === 'title'
        ) {
            // Follow the generic RCDATA element parsing algorithm.
            $this->parseGenericTextElement(
                $aToken,
                self::RCDATA_ELEMENT_ALGORITHM
            );
        } elseif ($aToken instanceof StartTagToken &&
            ($tagName === 'noscript' && $this->mParser->isScriptingEnabled()) ||
            ($tagName === 'noframes' || $tagName === 'style')
        ) {
            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement(
                $aToken,
                self::RAW_TEXT_ELEMENT_ALGORITHM
            );
        } elseif ($aToken instanceof StartTagToken &&
            $tagName === 'noscript' && !$this->mParser->isScriptingEnabled()
        ) {
            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Switch the insertion mode to "in head noscript".
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_HEAD_NOSCRIPT
            );
        } elseif ($aToken instanceof StartTagToken &&
            $tagName === 'script'
        ) {
            // Let the adjusted insertion location be the appropriate place for
            // inserting a node.
            $adjustedInsertionLocation =
                $this->getAppropriatePlaceForInsertingNode();

            // Create an element for the token in the HTML namespace, with the
            // intended parent being the element in which the adjusted insertion
            // location finds itself.
            $node = $this->createElementForToken(
                $aToken,
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
            if ($this->mParser->isFragmentCase()) {
                // TODO
            }

            // Insert the newly created element at the adjusted insertion
            // location.
            $this->insertNode($node, $adjustedInsertionLocation);

            // Push the element onto the stack of open elements so that it is
            // the new current node.
            $this->mOpenElements->push($node);

            // Switch the tokenizer to the script data state.
            $this->mParser->setTokenizerState(TokenizerState::SCRIPT_DATA);

            // Let the original insertion mode be the current insertion mode.
            $this->mParser->setOriginalInsertionMode(
                $this->mParser->getInsertionMode()
            );

            // Switch the insertion mode to "text".
            $this->mParser->setInsertionMode(ParserInsertionMode::TEXT);
        } elseif ($aToken instanceof EndTagToken && $tagName === 'head') {
            // Pop the current node (which will be the head element) off the
            // stack of open elements.
            $this->mOpenElements->pop();

            // Switch the insertion mode to "after head".
            $this->mParser->setInsertionMode(ParserInsertionMode::AFTER_HEAD);
        } elseif ($aToken instanceof EndTagToken && ($tagName === 'body' ||
            $tagName === 'html' || $tagName === 'br')
        ) {
            // Act as described in the "anything else" entry below.

            // Pop the current node (which will be the head element) off the
            // stack of open elements.
            $this->mOpenElements->pop();

            // Switch the insertion mode to "after head".
            $this->mParser->setInsertionMode(ParserInsertionMode::AFTER_HEAD);

            // Reprocess the token.
            $this->run($aToken);
        } elseif ($aToken instanceof StartTagToken && $tagName === 'template') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Insert a marker at the end of the list of active formatting
            // elements.
            $this->mActiveFormattingElements->push(new Marker());

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';

            // Switch the insertion mode to "in template".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TEMPLATE);

            // Push "in template" onto the stack of template insertion modes so
            // that it is the new current template insertion mode.
            $this->mTemplateInsertionModes->push(
                ParserInsertionMode::IN_TEMPLATE
            );
        } elseif ($aToken instanceof EndTagToken && $tagName === 'template') {
            if (!$this->mOpenElements->containsTemplateElement()) {
                // Parse error.
                // Ignore the token.
            } else {
                // Generate all implied end tags thoroughly.
                $this->generateAllImpliedEndTagsThoroughly();

                // If the current node is not a template element, then this is
                // a parse error.
                if (!($this->mOpenElements->top()
                    instanceof HTMLTemplateElement)
                ) {
                    // Parse error
                }

                // Pop elements from the stack of open elements until a
                // template element has been popped from the stack.
                while (!$this->mOpenElements->isEmpty()) {
                    if ($this->mOpenElements->pop()
                        instanceof HTMLTemplateElement
                    ) {
                        break;
                    }
                }

                // Clear the list of active formatting elements up to the last
                // marker.
                $this->mActiveFormattingElements->clearUpToLastMarker();

                // Pop the current template insertion mode off the stack of
                // template insertion modes.
                $this->mTemplateInsertionModes->pop();

                // Reset the insertion mode appropriately.
                $this->mParser->resetInsertionMode();
            }
        } elseif (($aToken instanceof StartTagToken && $tagName === 'head') ||
            $aToken instanceof EndTagToken
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            // Pop the current node (which will be the head element) off the
            // stack of open elements.
            $this->mOpenElements->pop();

            // Switch the insertion mode to "after head".
            $this->mParser->setInsertionMode(ParserInsertionMode::AFTER_HEAD);

            // Reprocess the token.
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inheadnoscript
     * @param  Token  $aToken [description]
     * @return [type]         [description]
     */
    protected function inHeadNoScriptInsertionMode(Token $aToken)
    {
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($aToken instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($aToken instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($aToken instanceof EndTagToken && $tagName === 'noscript') {
            // Pop the current node (which will be a noscript element) from the
            // stack of open elements; the new current node will be a head
            // element.
            $this->mOpenElements->pop();

            // Switch the insertion mode to "in head".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_HEAD);
        } elseif (($aToken instanceof CharacterToken &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")) ||
            $aToken instanceof CommentToken ||
            ($aToken instanceof StartTagToken && ($tagName === 'basefont' ||
                $tagName === 'bgsound' || $tagName === 'link' ||
                $tagName === 'meta' || $tagName === 'noframes' ||
                $tagName === 'style'))
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($aToken instanceof EndTagToken && $tagName === 'br') {
            // Act as described in the "anything else" entry below.

            // Parse error.
            // Pop the current node (which will be a noscript element) from the
            // stack of open elements; the new current node will be a head
            // element.
            $this->mOpenElements->pop();

            // Switch the insertion mode to "in head".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_HEAD);

            // Reprocess the token.
            $this->run($aToken);
        } elseif (($aToken instanceof StartTagToken && ($tagName === 'head' ||
            $tagName === 'noscript')) ||
            $aToken instanceof EndTagToken
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            // Parse error.
            // Pop the current node (which will be a noscript element) from the
            // stack of open elements; the new current node will be a head
            // element.
            $this->mOpenElements->pop();

            // Switch the insertion mode to "in head".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_HEAD);

            // Reprocess the token.
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-head-insertion-mode
     *
     * @param Token $aToken The token currently being processed.
     */
    protected function afterHeadInsertionMode(Token $aToken)
    {
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($aToken instanceof CharacterToken &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Insert the character.
            $this->insertCharacter($aToken);
        } elseif ($aToken instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($aToken instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($aToken instanceof StartTagToken && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($aToken instanceof StartTagToken && $tagName === 'body') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';

            // Switch the insertion mode to "in body".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_BODY);
        } elseif ($aToken instanceof StartTagToken && $tagName === 'frameset') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Switch the insertion mode to "in frameset".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_FRAMESET);
        } elseif ($aToken instanceof StartTagToken && ($tagName === 'base' ||
            $tagName === 'basefont' || $tagName === 'bgsound' ||
            $tagName === 'link' || $tagName === 'meta' ||
            $tagName === 'noframes' || $tagName === 'script' ||
            $tagName === 'style' || $tagName === 'template' ||
            $tagName === 'title')
        ) {
            // Parse error
            // Push the node pointed to by the head element pointer onto the
            // stack of open elements.
            $this->mOpenElements->push($this->mParser->getHeadElementPointer());

            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);

            // Remove the node pointed to by the head element pointer from the
            // stack of open elements. (It might not be the current node at
            // this point.)
            // NOTE: The head element pointer cannot be null at this point.
            $this->mOpenElements->remove(
                $this->mParser->getHeadElementPointer()
            );
        } elseif ($aToken instanceof EndTagToken && $tagName === 'template') {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($aToken instanceof EndTagToken && ($tagName === 'body' ||
            $tagName === 'html' || $tagName === 'br')
        ) {
            // Act as described in the "anything else" entry below.

            // Insert an HTML element for a "body" start tag token with no
            // attributes.
            $this->insertForeignElement(
                new StartTagToken('body'),
                Namespaces::HTML
            );

            // Switch the insertion mode to "in body".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_BODY);

            // Reprocess the current token
            $this->run($aToken);
        } elseif (($aToken instanceof StartTagToken && $tagName === 'head') ||
            $aToken instanceof EndTagToken
        ) {
            // Parse error.
            // Ignore the token
        } else {
            // Insert an HTML element for a "body" start tag token with no
            // attributes.
            $this->insertForeignElement(
                new StartTagToken('body'),
                Namespaces::HTML
            );

            // Switch the insertion mode to "in body".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_BODY);

            // Reprocess the current token
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inbody
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inBodyInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN &&
            $aToken->data === "\x00"
        ) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert the token's character.
            $this->insertCharacter($aToken);
        } elseif ($tokenType == Token::CHARACTER_TOKEN) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert the token's character.
            $this->insertCharacter($aToken);

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html') {
            // Parse error.
            // If there is a template element on the stack of open elements,
            // then ignore the token.
            if ($this->mOpenElements->containsTemplateElement()) {
                return;
            }

            // Otherwise, for each attribute on the token, check to see if the
            // attribute is already present on the top element of the stack of
            // open elements. If it is not, add the attribute and its
            // corresponding value to that element.
            $firstOnStack = $this->mOpenElements[0];

            foreach ($aToken->attributes as $attr) {
                $name = $attr->name;

                if (!$firstOnStack->hasAttribute($name)) {
                    $firstOnStack->setAttribute($name, $attr->value);
                }
            }
        } elseif (($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'base' || $tagName === 'basefont' ||
                $tagName === 'bgsound' || $tagName === 'link' ||
                $tagName === 'meta' || $tagName === 'noframes' ||
                $tagName === 'script' || $tagName === 'style' ||
                $tagName === 'template' || $tagName === 'title')) ||
            ($tokenType == Token::END_TAG_TOKEN && $tagName === 'template')
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'body') {
            // Parse error.
            // If the second element on the stack of open elements is not a body
            // element, if the stack of open elements has only one node on it,
            // or if there is a template element on the stack of open elements,
            // then ignore the token. (fragment case)
            if (!($this->mOpenElements[1] instanceof HTMLBodyElement) ||
                count($this->mOpenElements) == 1 ||
                $this->mOpenElements->containsTemplateElement()
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
            $this->mFramesetOk = 'not ok';
            $body = $this->mOpenElements[1];

            foreach ($aToken->attributes as $attr) {
                $name = $attr->name;

                if (!$body->hasAttribute($name)) {
                    $body->setAttribute($name, $attr->value);
                }
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'frameset'
        ) {
            // Parse error.
            // If the stack of open elements has only one node on it, or if the
            // second element on the stack of open elements is not a body
            // element, then ignore the token. (fragment case)
            $count = count($this->mOpenElements);

            if ($count == 1 ||
                !($this->mOpenElements[$count - 2] instanceof HTMLBodyElement)
            ) {
                // Fragment case
                // Ignore the token
                return;
            }

            // If the frameset-ok flag is set to "not ok", ignore the token.
            if ($this->mFramesetOk === 'not ok') {
                // Ignore the token.
                return;
            }

            // Remove the second element on the stack of open elements from its
            // parent node, if it has one.
            if (($body = $this->mOpenElements[1]) &&
                ($parent = $body->parentNode)
            ) {
                $parent->removeChild($body);
            }

            // Pop all the nodes from the bottom of the stack of open elements,
            // from the current node up to, but not including, the root html
            // element.
            for ($i = $count - 1; $i > 0; $i--) {
                $this->mOpenElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Switch the insertion mode to "in frameset".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_FRAMESET);
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // If the stack of template insertion modes is not empty, then
            // process the token using the rules for the "in template"
            // insertion mode.
            if (!$this->mTemplateInsertionModes->isEmpty()) {
                $this->inTemplateInsertionMode($aToken);
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

            foreach ($this->mOpenElements as $el) {
                if (!($el instanceof HTMLElement &&
                    preg_match($pattern, $el->localName))
                ) {
                    // Parse error.
                    break;
                }
            }

            // Stop parsing.
            $this->mParser->stopParsing();
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'body') {
            // If the stack of open elements does not have a body element
            // in scope, this is a parse error; ignore the token.
            if (!$this->mOpenElements->hasElementInScope(
                'body',
                Namespaces::HTML
            )) {
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

            foreach ($this->mOpenElements as $el) {
                if (!($el instanceof HTMLElement &&
                    preg_match($pattern, $el->localName))
                ) {
                    // Parse error.
                    break;
                }
            }

            // Switch the insertion mode to "after body".
            $this->mParser->setInsertionMode(ParserInsertionMode::AFTER_BODY);
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'html') {
            // If the stack of open elements does not have a body element in
            // scope, this is a parse error; ignore the token.
            if (!$this->mOpenElements->hasElementInScope(
                'body',
                Namespaces::HTML
            )) {
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

            foreach ($this->mOpenElements as $el) {
                if (!($el instanceof HTMLElement &&
                    preg_match($pattern, $el->localName))
                ) {
                    // Parse error.
                    break;
                }
            }

            // Switch the insertion mode to "after body".
            $this->mParser->setInsertionMode(ParserInsertionMode::AFTER_BODY);

            // Reprocess the token.
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            preg_match(
                '/^(address|article|aside|blockquote|center|details|dialog|' .
                'dir|div|dl|fieldset|figcaption|figure|footer|header|hgroup|' .
                'main|menu|nav|ol|p|section|summary|ul)$/',
                $tagName
            )
        ) {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                $this->closePElement();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::START_TAG_TOKEN && ($tagName === 'h1' ||
            $tagName === 'h2' || $tagName === 'h3' || $tagName === 'h4' ||
            $tagName === 'h5' || $tagName === 'h6')
        ) {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                $this->closePElement();
            }

            // If the current node is an HTML element whose tag name is one of
            // "h1", "h2", "h3", "h4", "h5", or "h6", then this is a parse
            // error; pop the current node off the stack of open elements.
            if ($this->mOpenElements->top() instanceof HTMLHeadingElement) {
                // Parse error.
                $this->mOpenElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'pre' || $tagName === 'listing')
        ) {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                $this->closePElement();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // TODO: If the next token is a U+000A LINE FEED (LF) character
            // token, then ignore that token and move on to the next one.
            // (Newlines at the start of pre blocks are ignored as an authoring
            // convenience.)

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'form') {
            // If the form element pointer is not null, and there is no
            // template element on the stack of open elements, then this is a
            // parse error; ignore the token.
            if ($this->mParser->getFormElementPointer() &&
                !$this->mOpenElements->containsTemplateElement()
            ) {
                // Parse error.
                // Ignore the token.
            } else {
                // If the stack of open elements has a p element in button
                // scope, then close a p element.
                if ($this->mOpenElements->hasElementInButtonScope(
                    'p',
                    Namespaces::HTML
                )) {
                    $this->closePElement();
                }

                // Insert an HTML element for the token, and, if there is no
                // template element on the stack of open elements, set the
                // form element pointer to point to the element created.
                $node = $this->insertForeignElement($aToken, Namespaces::HTML);

                if (!$this->mOpenElements->containsTemplateElement()) {
                    $this->mParser->setFormElementPointer($node);
                }
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'li') {
            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';

            // Initialise node to be the current node (the bottommost node of
            // the stack).
            $node = $this->mOpenElements->top();
            $this->mOpenElements->rewind();

            // Step "Loop".
            while (true) {
                if ($node instanceof HTMLLIElement) {
                    // Generate implied end tags, except for li elements.
                    $this->generateImpliedEndTags('li');

                    // If the current node is not an li element, then this is a
                    // parse error.
                    if (!($this->mOpenElements->top()
                        instanceof HTMLLIElement)
                    ) {
                        // Parse error.
                    }

                    // Pop elements from the stack of open elements until an li
                    // element has been popped from the stack.
                    while (!$this->mOpenElements->isEmpty()) {
                        if ($this->mOpenElements->pop()
                            instanceof HTMLLIElement
                        ) {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                // If node is in the special category, but is not an address,
                //  div, or p element, then jump to the step labeled done below.
                if ($this->isSpecialNode($node) &&
                    !($node instanceof HTMLElement &&
                        (($name = $node->localName) === 'address' ||
                            $name === 'div' || $name === 'p'))
                ) {
                    break;
                } else {
                    // Otherwise, set node to the previous entry in the stack
                    // of open elements and return to the step labeled loop.
                    $this->mOpenElements->next();
                    $node = $this->mOpenElements->current();
                }
            }

            // Step "Done".
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                $this->closePElement();
            }

            // Finally, insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'dd' || $tagName === 'dt')
        ) {
            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';

            // Initialise node to be the current node (the bottommost node of
            // the stack).
            $node = $this->mOpenElements->top();
            $this->mOpenElements->rewind();

            // Step "Loop".
            while (true) {
                if ($node instanceof HTMLElement && $node->localName === 'dd') {
                    // Generate implied end tags, except for dd elements.
                    $this->generateImpliedEndTags('dd');

                    // If the current node is not a dd element, then this is a
                    // parse error.
                    $currentNode = $this->mOpenElements->top();

                    if (!($currentNode instanceof HTMLElement &&
                        $currentNode->localName === 'dd')
                    ) {
                        // Parse error.
                    }

                    // Pop elements from the stack of open elements until a dd
                    // element has been popped from the stack.
                    while (!$this->mOpenElements->isEmpty()) {
                        $popped = $this->mOpenElements->pop();

                        if ($popped instanceof HTMLElement &&
                            $popped->localName === 'dd'
                        ) {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                if ($node instanceof HTMLElement && $node->localName === 'dt') {
                    // Generate implied end tags, except for dt elements.
                    $this->generateImpliedEndTags('dt');

                    // If the current node is not a dt element, then this is a
                    // parse error.
                    if (!($currentNode instanceof HTMLElement &&
                        $currentNode->localName === 'dt')
                    ) {
                        // Parse error
                    }

                    // Pop elements from the stack of open elements until a dt
                    // element has been popped from the stack.
                    while (!$this->mOpenElements->isEmpty()) {
                        $popped = $this->mOpenElements->pop();

                        if ($popped instanceof HTMLElement &&
                            $popped->localName === 'dt'
                        ) {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                // If node is in the special category, but is not an address,
                // div, or p element, then jump to the step labeled done below.
                if ($this->isSpecialNode($node) &&
                    !($node instanceof HTMLElement &&
                        (($name = $node->localName) === 'address' ||
                            $name === 'div' || $name === 'p'))
                ) {
                    break;
                } else {
                    // Otherwise, set node to the previous entry in the stack of
                    // open elements and return to the step labeled loop.
                    $this->mOpenElements->next();
                    $node = $this->mOpenElements->current();
                }
            }

            // Step "Done".
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                $this->closePElement();
            }

            // Finally, insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'plaintext'
        ) {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                $this->closePElement();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Switch the tokenizer to the PLAINTEXT state.
            // NOTE: Once a start tag with the tag name "plaintext" has been
            // seen, that will be the last token ever seen other than character
            // tokens (and the end-of-file token), because there is no way to
            // switch out of the PLAINTEXT state.
            $this->mParser->setTokenizerState(TokenizerState::PLAINTEXT);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'button'
        ) {
            // If the stack of open elements has a button element in scope,
            // then run these substeps:
            if ($this->mOpenElements->hasElementInScope(
                'button',
                Namespaces::HTML
            )) {
                // Parse error.
                // Generate implied end tags.
                $this->generateImpliedEndTags();

                // Pop elements from the stack of open elements until a button
                // element has been popped from the stack.
                while (!$this->mOpenElements->isEmpty()) {
                    $popped = $this->mOpenElements->pop();

                    if ($popped instanceof HTMLButtonElement) {
                        break;
                    }
                }
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            preg_match(
                '/^(address|article|aside|blockquote|button|center|details|' .
                'dialog|dir|div|dl|fieldset|figcaption|figure|footer|header|' .
                'hgroup|listing|main|menu|nav|ol|pre|section|summary|ul)$/',
                $tagName
            )
        ) {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (!$this->mOpenElements->hasElementInScope(
                $tagName,
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            $isValid = $this->isHTMLElementWithName(
                $this->mOpenElements->top(),
                $tagName
            );

            if (!$isValid) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped
            // from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                $isValid = $this->isHTMLElementWithName(
                    $this->mOpenElements->pop(),
                    $tagName
                );

                if ($isValid) {
                    break;
                }
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'form') {
            if (!$this->mOpenElements->containsTemplateElement()) {
                // Let node be the element that the form element pointer is set
                // to, or null if it is not set to an element.
                $node = $this->mParser->getFormElementPointer();

                // Set the form element pointer to null.
                $this->mParser->setFormElementPointer(null);

                // If node is null or if the stack of open elements does not
                // have node in scope, then this is a parse error; abort these
                // steps and ignore the token.
                if ($node === null || !$this->mOpenElements->hasElementInScope(
                    $node->localName,
                    Namespaces::HTML
                )) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Generate implied end tags.
                $this->generateImpliedEndTags();

                // If the current node is not node, then this is a parse error.
                if ($this->mOpenElements->top() !== $node) {
                    // Parse error.
                }

                // Remove node from the stack of open elements.
                $this->mOpenElements->remove($node);

                return;
            }

            // If the stack of open elements does not have a form element
            // in scope, then this is a parse error; abort these steps and
            // ignore the token.
            if (!$this->mOpenElements->hasElementInScope(
                'form',
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // If the current node is not a form element, then this is a parse
            // error.
            if (!($this->mOpenElements->top() instanceof HTMLFormElement)) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until a form
            // element has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                if ($this->mOpenElements->pop() instanceof HTMLFormElement) {
                    break;
                }
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'p') {
            // If the stack of open elements does not have a p element in
            // button scope, then this is a parse error; insert an HTML element
            // for a "p" start tag token with no attributes.
            if (!$this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                // Parse error.
                $this->insertForeignElement(
                    new StartTagToken('p'),
                    Namespaces::HTML
                );
            }

            // Close a p element.
            $this->closePElement();
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'li') {
            // If the stack of open elements does not have an li element
            // in list item scope, then this is a parse error; ignore the token.
            if (!$this->mOpenElements->hasElementInListItemScope(
                'li',
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags, except for li elements.
            $this->generateImpliedEndTags('li');

            // If the current node is not an li element, then this is a parse
            // error.
            if (!($this->mOpenElements->top() instanceof HTMLLIElement)) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an li element
            // has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                if ($this->mOpenElements->pop() instanceof HTMLLIElement) {
                    break;
                }
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            ($tagName === 'dd' || $tagName === 'dt')
        ) {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (!$this->mOpenElements->hasElementInScope(
                $tagName,
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags, except for HTML elements with the
            // same tag name as the token.
            $this->generateImpliedEndTags($tagName, Namespaces::HTML);

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            $isValid = $this->isHTMLElementWithName(
                $this->mOpenElements->top(),
                $tagName
            );

            if (!$isValid) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped from
            // the stack.
            while (!$this->mOpenElements->isEmpty()) {
                $isValid = $this->isHTMLElementWithName(
                    $this->mOpenElements->pop(),
                    $tagName
                );

                if ($isValid) {
                    break;
                }
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'h1' ||
            $tagName === 'h2' || $tagName === 'h3' || $tagName === 'h4' ||
            $tagName === 'h5' || $tagName === 'h6')
        ) {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element and whose tag name is one of "h1",
            // "h2", "h3", "h4", "h5", or "h6", then this is a parse error;
            // ignore the token.
            if (!$this->mOpenElements->hasElementInScope(
                'h1',
                Namespaces::HTML
            ) &&
            !$this->mOpenElements->hasElementInScope(
                'h2',
                Namespaces::HTML
            ) &&
            !$this->mOpenElements->hasElementInScope(
                'h3',
                Namespaces::HTML
            ) &&
            !$this->mOpenElements->hasElementInScope(
                'h4',
                Namespaces::HTML
            ) &&
            !$this->mOpenElements->hasElementInScope(
                'h5',
                Namespaces::HTML
            ) &&
            !$this->mOpenElements->hasElementInScope(
                'h6',
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            $isValid = $this->isHTMLElementWithName(
                $this->mOpenElements->top(),
                $tagName
            );

            if (!$isValid) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element whose tag name is one of "h1", "h2", "h3", "h4", "h5",
            // or "h6" has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                if ($this->mOpenElements->pop() instanceof HTMLHeadingElement) {
                    break;
                }
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'a') {
            // If the list of active formatting elements contains an a element
            // between the end of the list and the last marker on the list (or
            // the start of the list if there is no marker on the list), then
            // this is a parse error; run the adoption agency algorithm for the
            // token, then remove that element from the list of active
            // formatting elements and the stack of open elements if the
            // adoption agency algorithm didn't already remove it (it might not
            // have if the element is not in table scope).
            if (!$this->mActiveFormattingElements->isEmpty()) {
                $hasAnchorElement = false;

                foreach ($this->mActiveFormattingElements as $element) {
                    if ($element instanceof Marker) {
                        break;
                    } else {
                        $hasAnchorElement = true;
                        break;
                    }
                }

                if ($hasAnchorElement) {
                    // Parse error.
                    $element = $this->adoptionAgency($aToken);

                    if ($element &&
                        $this->mActiveFormattingElements->contains($element)
                    ) {
                        $this->mActiveFormattingElements->remove($element);
                    }

                    if ($element && $this->mOpenElements->contains($element)) {
                        $this->mOpenElements->remove($element);
                    }
                }
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mActiveFormattingElements->push($node);
        } elseif ($tokenType == Token::START_TAG_TOKEN && ($tagName === 'b' ||
            $tagName === 'big' || $tagName === 'code' || $tagName === 'em' ||
            $tagName === 'font' || $tagName === 'i' || $tagName === 's' ||
            $tagName === 'small' || $tagName === 'strike' ||
            $tagName === 'strong' || $tagName === 'tt' || $tagName === 'u')
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mActiveFormattingElements->push($node);
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'nobr') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // If the stack of open elements has a nobr element in scope,
            // then this is a parse error; run the adoption agency algorithm for
            // the token, then once again reconstruct the active formatting
            // elements, if any.
            if ($this->mOpenElements->hasElementInScope(
                'nobr',
                Namespaces::HTML
            )) {
                // Parse error.
                $this->adoptionAgency($aToken);
                $this->reconstructActiveFormattingElements();
            }

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mActiveFormattingElements->push($node);
        } elseif ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'a' ||
            $tagName === 'b' || $tagName === 'big' || $tagName === 'code' ||
            $tagName === 'em' || $tagName === 'font' || $tagName === 'i' ||
            $tagName === 'nobr' || $tagName === 's' || $tagName === 'small' ||
            $tagName === 'strike' || $tagName === 'strong' ||
            $tagName === 'tt' || $tagName === 'u')
        ) {
            // Run the adoption agency algorithm for the token.
            $this->adoptionAgency($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'applet' || $tagName === 'marquee' ||
                $tagName === 'object')
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Insert a marker at the end of the list of active formatting
            // elements.
            $this->mActiveFormattingElements->push(new Marker());

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            ($tagName === 'applet' || $tagName === 'marquee' ||
                $tagName === 'object')
        ) {
            // If the stack of open elements does not have an element in scope
            // that is an HTML element with the same tag name as that of the
            // token, then this is a parse error; ignore the token.
            if (false) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            $isValid = $this->isHTMLElementWithName(
                $this->mOpenElements->top(),
                $tagName
            );

            if (!$isValid) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped from
            // the stack.
            while (!$this->mOpenElements->isEmpty()) {
                $isValid = $this->isHTMLElementWithName(
                    $this->mOpenElements->pop(),
                    $tagName
                );

                if ($isValid) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $this->mActiveFormattingElements->clearUpToLastMarker();
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'table'
        ) {
            // If the Document is not set to quirks mode, and the stack of
            // open elements has a p element in button scope, then close a p
            // element.
            if ($this->mDocument->getMode() != DocumentMode::QUIRKS &&
                $this->mOpenElements->hasElementInButtonScope(
                    'p',
                    Namespaces::HTML
                )
            ) {
                $this->closePElement();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';

            // Switch the insertion mode to "in table".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE);
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'br') {
            // Parse error.
            // Drop the attributes from the token, and act as described in the
            // next entry; i.e. act as if this was a "br" start tag token with
            // no attributes, rather than the end tag token that it actually is.
            $aToken->clearAttributes();

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'area' || $tagName === 'br' || $tagName === 'img' ||
                $tagName === 'embed' || $tagName === 'keygen' ||
                $tagName === 'wbr')
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'input'
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }

            // If the token does not have an attribute with the name "type", or
            // if it does, but that attribute's value is not an ASCII
            // case-insensitive match for the string "hidden", then: set the
            // frameset-ok flag to "not ok".
            $typeAttribute = null;

            foreach ($aToken->attributes as $attr) {
                if ($attr->name === 'type') {
                    $typeAttribute = $attr;
                    break;
                }
            }

            if (!$typeAttribute ||
                ($typeAttribute && strcasecmp(
                    $typeAttribute->value,
                    'hidden'
                ) !== 0)
            ) {
                $this->mFramesetOk = 'not ok';
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'param' || $tagName === 'source' ||
                $tagName === 'track')
        ) {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'hr') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                $this->closePElement();
            }

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'image'
        ) {
            // Parse error.
            // Change the token's tag name to "img" and reprocess it. (Don't
            // ask.)
            $aToken->tagName = 'img';
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'textarea'
        ) {
            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // TODO: If the next token is a U+000A LINE FEED (LF) character
            // token, then ignore that token and move on to the next one.
            // (Newlines at the start of textarea elements are ignored as an
            // authoring convenience.)

            // Switch the tokenizer to the RCDATA state.
            $this->mParser->setTokenizerState(TokenizerState::RCDATA);

            // Let the original insertion mode be the current insertion mode.
            $this->mParser->setOriginalInsertionMode(
                $this->mParser->getInsertionMode()
            );

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';

            // Switch the insertion mode to "text".
            $this->mParser->setInsertionMode(ParserInsertionMode::TEXT);
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'xmp') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($this->mOpenElements->hasElementInButtonScope(
                'p',
                Namespaces::HTML
            )) {
                $this->closePElement();
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Set the frameset-ok flag to "not ok
            $this->mFramesetOk = 'not ok';

            //Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement(
                $aToken,
                self::RAW_TEXT_ELEMENT_ALGORITHM
            );
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'iframe'
        ) {
            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';

            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement(
                $aToken,
                self::RAW_TEXT_ELEMENT_ALGORITHM
            );
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'noembed' || ($tagName === 'noscript' &&
                $this->mParser->isScriptingEnabled()))
        ) {
            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement(
                $aToken,
                self::RAW_TEXT_ELEMENT_ALGORITHM
            );
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'select'
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';

            // If the insertion mode is one of "in table", "in caption",
            // "in table body", "in row", or "in cell", then switch the
            // insertion mode to "in select in table". Otherwise, switch the
            // insertion mode to "in select".
            switch ($this->mParser->getInsertionMode()) {
                case ParserInsertionMode::IN_TABLE:
                case ParserInsertionMode::IN_CAPTION:
                case ParserInsertionMode::IN_TABLE_BODY:
                case ParserInsertionMode::IN_ROW:
                case ParserInsertionMode::IN_CELL:
                    $this->mParser->setInsertionMode(
                        ParserInsertionMode::IN_SELECT_IN_TABLE
                    );

                    break;

                default:
                    $this->mParser->setInsertionMode(
                        ParserInsertionMode::IN_SELECT
                    );
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'optgroup' || $tagName === 'option')
        ) {
            // If the current node is an option element, then pop the current
            // node off the stack of open elements.
            if ($this->mOpenElements->top() instanceof HTMLOptionElement) {
                $this->mOpenElements->pop();
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'rb' || $tagName === 'rtc')
        ) {
            // If the stack of open elements has a ruby element in scope,
            // then generate implied end tags. If the current node is not now a
            // ruby element, this is a parse error.
            if ($this->mOpenElements->hasElementInScope(
                'ruby',
                Namespaces::HTML
            )) {
                $this->generateImpliedEndTags();
                $currentNode = $this->mOpenElements->top();

                if (!($currentNode instanceof HTMLElement &&
                    $currentNode->localName === 'ruby')
                ) {
                    // Parse error.
                }
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'rp' || $tagName === 'rt')
        ) {
            // If the stack of open elements has a ruby element in scope,
            // then generate implied end tags, except for rtc elements. If the
            // current node is not now a rtc element or a ruby element, this is
            // a parse error.
            if ($this->mOpenElements->hasElementInScope(
                'ruby',
                Namespaces::HTML
            )) {
                $this->generateImpliedEndTags('rtc');
                $currentNode = $this->mOpenElements->top();

                if (!($currentNode instanceof HTMLElement &&
                    $currentNode->localName === 'rtc')
                ) {
                    // Parse error.
                }
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'math') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Adjust MathML attributes for the token. (This fixes the case of
            // MathML attributes that are not all lowercase.)
            $this->adjustMathMLAttributes($aToken);

            // Adjust foreign attributes for the token. (This fixes the use of
            // namespaced attributes, in particular XLink.)
            $this->adjustForeignAttributes($aToken);

            // Insert a foreign element for the token, in the MathML namespace.
            $this->insertForeignElement($aToken, Namespaces::MATHML);

            // If the token has its self-closing flag set, pop the current node
            // off the stack of open elements and acknowledge the token's
            // self-closing flag.
            if ($aToken->isSelfClosing()) {
                $this->mOpenElements->pop();
                $aToken->acknowledge();
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'svg') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Adjust SVG attributes for the token. (This fixes the case of SVG
            // attributes that are not all lowercase.)
            $this->adjustSVGAttributes($aToken);

            // Adjust foreign attributes for the token. (This fixes the use of
            // namespaced attributes, in particular XLink in SVG.)
            $this->adjustForeignAttributes($aToken);

            // Insert a foreign element for the token, in the SVG namespace.
            $this->insertForeignElement($aToken, Namespaces::SVG);

            // If the token has its self-closing flag set, pop the current node
            // off the stack of open elements and acknowledge the token's
            // self-closing flag.
            if ($aToken->isSelfClosing()) {
                $this->mOpenElements->pop();
                $aToken->acknowledge();
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'caption' || $tagName === 'col' ||
                $tagName === 'colgroup' || $tagName === 'frame' ||
                $tagName === 'head' || $tagName === 'tbody' ||
                $tagName === 'td' || $tagName === 'tfoot' ||
                $tagName === 'th' || $tagName === 'thead' ||
                $tagName === 'tr')
        ) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements();

            // Insert an HTML element for the token.
            // NOTE: This element will be an ordinary element.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::END_TAG_TOKEN) {
            $this->applyAnyOtherEndTagForInBodyInsertionMode($aToken);
        }
    }

    protected function applyAnyOtherEndTagForInBodyInsertionMode(Token $aToken)
    {
        // Initialise node to be the current node (the bottommost node of the
        // stack).
        $node = $this->mOpenElements->top();
        $this->mOpenElements->rewind();
        $tagName = $aToken->tagName;

        while ($this->mOpenElements->valid()) {
            if ($this->isHTMLElementWithName($node, $tagName)) {
                // Generate implied end tags, except for HTML elements with
                // the same tag name as the token.
                $this->generateImpliedEndTags($tagName);

                // If node is not the current node, then this is a parse error.
                if ($node !== $this->mOpenElements->top()) {
                    // Parse error.
                }

                // Pop all the nodes from the current node up to node, including
                // node, then stop these steps.
                while (!$this->mOpenElements->isEmpty()) {
                    if ($this->mOpenElements->pop() === $node) {
                        break;
                    }
                }
            } elseif ($this->isSpecialNode($node)) {
                // Parse error.
                // Ignore the token.
                break;
            }

            // Set node to the previous entry in the stack of open elements.
            $this->mOpenElements->next();
            $node = $this->mOpenElements->current();
        }
    }

    /**
     * Closes a paragraph (p) element.
     *
     * @see https://html.spec.whatwg.org/multipage/#close-a-p-element
     */
    protected function closePElement()
    {
        $this->generateImpliedEndTags('p');

        if (!($this->mOpenElements->top() instanceof HTMLParagraphElement)) {
            // Parse error
        }

        while (!$this->mOpenElements->isEmpty()) {
            if ($this->mOpenElements->pop() instanceof HTMLParagraphElement) {
                break;
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adoption-agency-algorithm
     * @param  TagToken $aToken [description]
     * @return [type]           [description]
     */
    protected function adoptionAgency(TagToken $aToken)
    {
        $subject = $aToken->tagName;
        $currentNode = $this->mOpenElements->top();

        // If the current node is an HTML Element with a tag name that matches
        // subject and the current node is not in the list of active formatting
        // elements, then remove the current node from the stack of open
        // elements and abort these steps.
        if ($this->isHTMLElementWithName($currentNode, $subject) &&
            !$this->mActiveFormattingElements->contains($currentNode)
        ) {
            $this->mOpenElements->pop();

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

            foreach ($this->mActiveFormattingElements as $e) {
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
                $this->applyAnyOtherEndTagForInBodyInsertionMode($aToken);
                return;
            }

            // If formatting element is not in the stack of open elements, then
            // this is a parse error; remove the element from the list, and
            // abort these steps.
            if (!$this->mOpenElements->contains($formattingElement)) {
                // Parse error.
                $this->mActiveFormattingElements->remove($formattingElement);
                return;
            }

            // If formatting element is in the stack of open elements, but
            // the element is not in scope, then this is a parse error; abort
            // these steps.
            if ($this->mOpenElements->contains($formattingElement) &&
                !$this->mOpenElements->hasElementInScope(
                    $formattingElement->localName,
                    $formattingElement->namespaceURI
                )
            ) {
                // Parse error.
                return;
            }

            // If formatting element is not the current node, this is a parse
            // error. (But do not abort these steps.)
            if ($this->mOpenElements->top() !== $formattingElement) {
                // Parse error.
            }

            // Let furthest block be the topmost node in the stack of open
            // elements that is lower in the stack than formatting element, and
            // is an element in the special category. There might not be one.
            $furthestBlock = null;
            $this->mOpenElements->seek($formattingElement);
            $this->mOpenElements->prev();

            while ($this->mOpenElements->valid()) {
                $current = $this->mOpenElements->current();

                if ($this->isSpecialNode($current)) {
                    $furthestBlock = $current;
                    break;
                }

                $this->mOpenElements->prev();
            }

            // If there is no furthest block, then the UA must first pop all the
            // nodes from the bottom of the stack of open elements, from the
            // current node up to and including formatting element, then remove
            // formatting element from the list of active formatting elements,
            // and finally abort these steps.
            if (!$furthestBlock) {
                while (!$this->mOpenElements->isEmpty()) {
                    if ($this->mOpenElements->pop() === $formattingElement) {
                        break;
                    }
                }

                $this->mActiveFormattingElements->remove($formattingElement);
                return;
            }

            // Let common ancestor be the element immediately above formatting
            // element in the stack of open elements.
            $this->mOpenElements->seek($formattingElement);
            $this->mOpenElements->next();
            $commonAncestor = $this->mOpenElements->current();

            // Let a bookmark note the position of formatting element in the
            // list of active formatting elements relative to the elements on
            // either side of it in the list.
            $bookmark = new Bookmark();
            $this->mActiveFormattingElements->insertAfter(
                $bookmark,
                $formattingElement
            );

            // Let node and last node be furthest block.
            $node = $furthestBlock;
            $lastNode = $furthestBlock;

            // Let inner loop counter be zero.
            $innerLoopCounter = 0;
            $clonedStack = clone $this->mOpenElements;

            // Inner loop
            while (true) {
                // Increment inner loop counter by one.
                $innerLoopCounter++;

                // Let node be the element immediately above node in the stack
                // of open elements, or if node is no longer in the stack of
                // open elements (e.g. because it got removed by this
                // algorithm), the element that was immediately above node in
                // the stack of open elements before node was removed.
                $stack = $this->mOpenElements;

                if (!$stack->contains($node)) {
                    $stack = $clonedStack;
                }

                $stack->seek($node);
                $stack->next();
                $node = $stack->current();

                // If node is formatting element, then go to the next step in
                // the overall algorithm.
                if ($node === $formattingElement) {
                    break;
                }

                // If inner loop counter is greater than three and node is in
                // the list of active formatting elements, then remove node from
                // the list of active formatting elements.
                $nodeInList = $this->mActiveFormattingElements->contains($node);

                if ($innerLoopCounter > 3 && $nodeInList) {
                    $this->mActiveFormattingElements->remove($node);
                    $nodeInList = false;
                }

                // If node is not in the list of active formatting elements,
                // then remove node from the stack of open elements and then go
                // back to the step labeled inner loop.
                if (!$nodeInList) {
                    $this->mOpenElements->remove($node);
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
                    $this->mTokenRepository[$node],
                    Namespaces::HTML,
                    $commonAncestor
                );
                $this->mTokenRepository->attach(
                    $newElement,
                    $this->mTokenRepository[$node]
                );

                $this->mActiveFormattingElements->replace($newElement, $node);
                $this->mOpenElements->replace($node, $newElement);
                $node = $newElement;

                // If last node is furthest block, then move the aforementioned
                // bookmark to be immediately after the new node in the list of
                // active formatting elements.
                if ($lastNode === $furthestBlock) {
                    $this->mActiveFormattingElements->remove($bookmark);
                    $this->mActiveFormattingElements->insertAfter(
                        $bookmark,
                        $newElement
                    );
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
                $this->mTokenRepository[$formattingElement],
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
            $this->mActiveFormattingElements->remove($formattingElement);
            $this->mActiveFormattingElements->replace($element, $bookmark);

            // Remove formatting element from the stack of open elements, and
            // insert the new element into the stack of open elements
            // immediately below the position of furthest block in that stack.
            $this->mOpenElements->remove($formattingElement);
            $this->mOpenElements->insertAfter($furthestBlock, $element);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incdata
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inTextInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();

        if ($tokenType == Token::CHARACTER_TOKEN) {
            // Insert the token's character.
            // NOTE: This can never be a U+0000 NULL character; the tokenizer
            // converts those to U+FFFD REPLACEMENT CHARACTER characters.
            $this->insertCharacter($aToken);
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // Parse error.
            // If the current node is a script element, mark the script element
            // as "already started".
            if ($this->mOpenElements->top() instanceof HTMLScriptElement) {
                // TODO: Mark the script element as "already started".
            }

            // Pop the current node off the stack of open elements.
            $this->mOpenElements->pop();

            // Switch the insertion mode to the original insertion mode and
            // reprocess the token.
            $this->mParser->setInsertionMode(
                $this->mParser->getOriginalInsertionMode()
            );
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            $aToken->tagName === 'script'
        ) {
            // TODO: If the JavaScript execution context stack is empty, perform
            // a microtask checkpoint.

            // Let script be the current node (which will be a script element).
            $script = $this->mOpenElements->top();

            // Pop the current node off the stack of open elements.
            $this->mOpenElements->pop();

            // Switch the insertion mode to the original insertion mode.
            $this->mParser->setInsertionMode(
                $this->mParser->getOriginalInsertionMode()
            );

            // TODO: More stuff that will probably never be fully supported
        } elseif ($tokenType == Token::END_TAG_TOKEN) {
            // Pop the current node off the stack of open elements.
            $this->mOpenElements->pop();

            // Switch the insertion mode to the original insertion mode.
            $this->mParser->setInsertionMode(
                $this->mParser->getOriginalInsertionMode()
            );
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intable
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inTableInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN &&
            ($currentNode = $this->mOpenElements->top()) &&
            ($currentNode instanceof HTMLTableElement ||
            $currentNode instanceof HTMLTableSectionElement ||
            $currentNode instanceof HTMLTableRowElement)
        ) {
            // Let the pending table character tokens be an empty list of
            // tokens.
            $this->mPendingTableCharacterTokens = [];

            // Let the original insertion mode be the current insertion mode.
            $this->mParser->setOriginalInsertionMode(
                $this->mParser->getInsertionMode()
            );

            // Switch the insertion mode to "in table text" and reprocess the
            // token.
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_TABLE_TEXT
            );
            $this->run($aToken);
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'caption'
        ) {
            // Clear the stack back to a table context.
            $this->mOpenElements->clearBackToTableContext();

            // Insert a marker at the end of the list of active formatting
            // elements.
            $this->mActiveFormattingElements->push(new Marker());

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in caption".
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_CAPTION);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'colgroup'
        ) {
            // Clear the stack back to a table context.
            $this->mOpenElements->clearBackToTableContext();

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in column group".
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_COLUMN_GROUP
            );
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'col') {
            // Clear the stack back to a table context.
            $this->mOpenElements->clearBackToTableContext();

            // Insert an HTML element for a "colgroup" start tag token with no
            // attributes, then switch the insertion mode to "in column group".
            $this->insertForeignElement(
                new StartTagToken('colgroup'),
                Namespaces::HTML
            );
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_COLUMN_GROUP
            );

            // Reprocess the current token.
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'tbody' || $tagName === 'tfoot' ||
                $tagName === 'thead')
        ) {
            // Clear the stack back to a table context.
            $this->mOpenElements->clearBackToTableContext();

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in table body".
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_TABLE_BODY
            );
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'td' || $tagName === 'th' || $tagName === 'tr')
        ) {
            // Clear the stack back to a table context.
            $this->mOpenElements->clearBackToTableContext();

            // Insert an HTML element for a "tbody" start tag token with no
            // attributes, then switch the insertion mode to "in table body".
            $this->insertForeignElement(
                new StartTagToken('tbody'),
                Namespaces::HTML
            );
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_TABLE_BODY
            );

            // Reprocess the current token.
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'table'
        ) {
            // Parse error.
            // If the stack of open elements does not have a table element
            // in table scope, ignore the token.
            if (!$this->mOpenElements->hasElementInTableScope(
                'table',
                Namespaces::HTML
            )) {
                // Ignore the token.
                return;
            }

            // Pop elements from this stack until a table element has been
            // popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                $popped = $this->mOpenElements->pop();

                if ($popped instanceof HTMLTableElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->mParser->resetInsertionMode();

            // Reprocess the token.
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'table') {
            // If the stack of open elements does not have a table element in
            // table scope, this is a parse error; ignore the token.
            if (false) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Pop elements from this stack until a table element has been
            // popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                $popped = $this->mOpenElements->pop();

                if ($popped instanceof HTMLTableElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->mParser->resetInsertionMode();
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            ($tagName === 'body' || $tagName === 'caption' ||
                $tagName === 'col' || $tagName === 'colgroup' ||
                $tagName === 'html' || $tagName === 'tbody' ||
                $tagName === 'td' || $tagName === 'tfoot' ||
                $tagName === 'th' || $tagName === 'thead' || $tagName === 'tr')
        ) {
            // Parse error.
            // Ignore the token.
        } elseif (($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'style' || $tagName === 'script' ||
                $tagName === 'template')) ||
            ($tokenType == Token::END_TAG_TOKEN && $tagName === 'template')
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'input'
        ) {
            // If the token does not have an attribute with the name "type", or
            // if it does, but that attribute's value is not an ASCII
            // case-insensitive match for the string "hidden", then: act as
            // described in the "anything else" entry below.
            $typeAttr = null;

            foreach ($aToken->attributes as $attr) {
                if ($attr->name === 'type') {
                    $typeAttr = $attr;
                    break;
                }
            }

            if (!$typeAttr ||
                ($typeAttr && strcasecmp($typeAttr->value, 'hidden') !== 0)
            ) {
                // Parse error.
                // Enable foster parenting, process the token using the rules
                // for the "in body" insertion mode, and then disable foster
                // parenting.
                $this->mFosterParenting = true;
                $this->inBodyInsertionMode($aToken);
                $this->mFosterParenting = false;
                return;
            }

            // Parse error.
            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);

            // Pop that input element off the stack of open elements.
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'form') {
            // Parse error.
            // If there is a template element on the stack of open elements, or
            // if the form element pointer is not null, ignore the token.
            if ($this->mOpenElements->containsTemplateElement() ||
                $this->mParser->getFormElementPointer() !== null
            ) {
                // Ignore the token.
                return;
            }

            // Insert an HTML element for the token, and set the form element
            // pointer to point to the element created.
            $this->mParser->setFormElementPointer(
                $this->insertForeignElement($aToken, Namespaces::HTML)
            );

            // Pop that form element off the stack of open elements.
            $this->mOpenElements->pop();
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } else {
            // Parse error.
            // Enable foster parenting, process the token using the rules for
            // the "in body" insertion mode, and then disable foster parenting.
            $this->mFosterParenting = true;
            $this->inBodyInsertionMode($aToken);
            $this->mFosterParenting = false;
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intabletext
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inTableTextInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();

        if ($tokenType == Token::CHARACTER_TOKEN &&
            $aToken->data === "\x00"
        ) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::CHARACTER_TOKEN) {
            // Append the character token to the pending table character tokens
            // list.
            $this->mPendingTableCharacterTokens[] = $aToken;
        } else {
            // If any of the tokens in the pending table character tokens list
            // are character tokens that are not space characters, then this is
            // a parse error: reprocess the character tokens in the pending
            // table character tokens list using the rules given in the
            // "anything else" entry in the "in table" insertion mode.
            $containsNonSpaceCharacters = false;

            foreach ($this->mPendingTableCharacterTokens as $token) {
                $data = $token->data;

                if ($data !== "\x20" && $data !== "\x09" && $data !== "\x0A" &&
                    $data !== "\x0C" && $data !== "\x0D"
                ) {
                    $containsNonSpaceCharacters = true;
                    break;
                }
            }

            foreach ($this->mPendingTableCharacterTokens as $token) {
                if ($containsNonSpaceCharacters) {
                    // Parse error.
                    // Enable foster parenting, process the token using the
                    // rules for the "in body" insertion mode, and then disable
                    // foster parenting.
                    $this->mFosterParenting = true;
                    $this->inBodyInsertionMode($token);
                    $this->mFosterParenting = false;
                } else {
                    // Otherwise, insert the characters given by the pending
                    // table character tokens list.
                    $this->insertCharacter($token);
                }
            }

            // Switch the insertion mode to the original insertion mode and
            // reprocess the token.
            $this->mParser->setInsertionMode(
                $this->mParser->getOriginalInsertionMode()
            );
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incaption
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inCaptionInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::END_TAG_TOKEN && $tagName === 'caption') {
            // If the stack of open elements does not have a caption element in
            // table scope, this is a parse error; ignore the token. (fragment
            // case)
            if (!$this->mOpenElements->hasElementInTableScope(
                'caption',
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // Now, if the current node is not a caption element, then this is
            // a parse error.
            if (!($this->mOpenElements->top()
                instanceof HTMLTableCaptionElement)
            ) {
                // Parse error.
            }

            // Pop elements from this stack until a caption element has been
            // popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                $popped = $this->mOpenElements->pop();

                if ($popped instanceof HTMLTableCaptionElement) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $this->mActiveFormattingElements->clearUpToLastMarker();

            // Switch the insertion mode to "in table".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE);
        } elseif (($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'caption' || $tagName === 'col' ||
                $tagName === 'colgroup' || $tagName === 'tbody' ||
                $tagName === 'td' || $tagName === 'tfoot' ||
                $tagName === 'th' || $tagName === 'thead' ||
                $tagName === 'tr')) ||
            ($tokenType == Token::END_TAG_TOKEN && $tagName === 'table')
        ) {
            // If the stack of open elements does not have a caption element
            // in table scope, this is a parse error; ignore the token.
            // (fragment case)
            if (!$this->mOpenElements->hasElementInTableScope(
                'caption',
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
            } else {
                // Generate implied end tags.
                $this->generateImpliedEndTags();

                // Now, if the current node is not a caption element, then this
                // is a parse error.
                if (!($this->mOpenElements->top()
                    instanceof HTMLTableCaptionElement)
                ) {
                    // Parse error.
                }

                // Pop elements from this stack until a caption element has
                // been popped from the stack.
                while (!$this->mOpenElements->isEmpty()) {
                    $popped = $this->mOpenElements->pop();

                    if ($popped instanceof HTMLTableCaptionElement) {
                        break;
                    }
                }

                // Clear the list of active formatting elements up to the last
                // marker.
                $this->mActiveFormattingElements->clearUpToLastMarker();

                // Switch the insertion mode to "in table".
                $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE);

                // Reprocess the token.
                $this->run($aToken);
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            ($tagName === 'caption' || $tagName === 'col' ||
                $tagName === 'colgroup' || $tagName === 'tbody' ||
                $tagName === 'td' || $tagName === 'tfoot' ||
                $tagName === 'th' || $tagName === 'thead' || $tagName === 'tr')
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incolgroup
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inColumnGroupInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Insert the character.
            $this->insertCharacter($aToken);
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'col') {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            $tagName === 'colgroup'
        ) {
            // If the current node is not a colgroup element, then this is a
            // parse error; ignore the token.
            $currentNode = $this->mOpenElements->top();

            if (!($currentNode instanceof HTMLTableColElement &&
                $currentNode->localName === 'colgroup')
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, pop the current node from the stack of open elements.
            // Switch the insertion mode to "in table".
            $this->mOpenElements->pop();
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE);
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'col') {
            // Parse error.
            // Ignore the token.
        } elseif (($tokenType == Token::START_TAG_TOKEN ||
            $tokenType == Token::END_TAG_TOKEN) && $tagName === 'template'
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } else {
            // If the current node is not a colgroup element, then this is a
            // parse error; ignore the token.
            $currentNode = $this->mOpenElements->top();

            if (!($currentNode instanceof HTMLTableColElement &&
                $currentNode->localName === 'colgroup')
            ) {
                // Parse error.
                // Ignore the token.
            } else {
                // Otherwise, pop the current node from the stack of open
                // elements.
                $this->mOpenElements->pop();
            }

            // Switch the insertion mode to "in table".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE);

            // Reprocess the token.
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intbody
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inTableBodyInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::START_TAG_TOKEN && $tagName === 'tr') {
            // Clear the stack back to a table body context.
            $this->mOpenElements->clearBackToTableBodyContext();

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in row".
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_ROW);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'th' || $tagName === 'td')
        ) {
            // Parse error.
            // Clear the stack back to a table body context.
            $this->mOpenElements->clearBackToTableBodyContext();

            // Insert an HTML element for a "tr" start tag token with no
            // attributes, then switch the insertion mode to "in row".
            $this->insertForeignElement(
                new StartTagToken('tr'),
                Namespaces::HTML
            );
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_ROW);

            // Reprocess the current token.
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            ($tagName === 'tbody' || $tagName === 'thead' ||
                $tagName === 'tfoot')
        ) {
            // If the stack of open elements does not have an element in table
            // scope that is an HTML element with the same tag name as the
            // token, this is a parse error; ignore the token.
            if (!$this->mOpenElements->hasElementInTableScope(
                $tagName,
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table body context.
            $this->mOpenElements->clearBackToTableBodyContext();

            // Pop the current node from the stack of open elements. Switch the
            // insertion mode to "in table".
            $this->mOpenElements->pop();
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE);
        } elseif (($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'caption' || $tagName === 'col' ||
                $tagName === 'colgroup' || $tagName === 'tbody' ||
                $tagName === 'tfoot' || $tagName === 'thead')) ||
            ($tokenType == Token::END_TAG_TOKEN && $tagName === 'table')
        ) {
            // If the stack of open elements does not have a tbody, thead, or
            // tfoot element in table scope, this is a parse error; ignore the
            // token.
            if (!$this->mOpenElements->hasElementInTableScope(
                    'tbody',
                    Namespaces::HTML
                ) &&
                !$this->mOpenElements->hasElementInTableScope(
                    'tbody',
                    Namespaces::HTML
                ) &&
                !$this->mOpenElements->hasElementInTableScope(
                    'tbody',
                    Namespaces::HTML
                )
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table body context.
            $this->mOpenElements->clearBackToTableBodyContext();

            // Pop the current node from the stack of open elements. Switch the
            // insertion mode to "in table".
            $this->mOpenElements->pop();
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE);

            // Reprocess the token.
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            ($tagName === 'body' || $tagName === 'caption' ||
                $tagName === 'col' || $tagName === 'colgroup' ||
                $tagName === 'html' || $tagName === 'td' || $tagName === 'th' ||
                $tagName === 'tr')
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            // Process the token using the rules for the "in table" insertion
            // mode.
            $this->inTableInsertionMode($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intr
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inRowInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::START_TAG_TOKEN && ($tagName === 'th' ||
            $tagName === 'td')
        ) {
            // Clear the stack back to a table row context.
            $this->mOpenElements->clearBackToTableRowContext();

            // Insert an HTML element for the token, then switch the insertion
            // mode to "in cell".
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_CELL);

            // Insert a marker at the end of the list of active formatting
            // elements.
            $this->mActiveFormattingElements->push(new Marker());
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'tr') {
            // If the stack of open elements does not have a tr element in
            // table scope, this is a parse error; ignore the token.
            if (false) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table row context.
            $this->mOpenElements->clearBackToTableRowContext();

            // Pop the current node (which will be a tr element) from the stack
            // of open elements. Switch the insertion mode to "in table body".
            $this->mOpenElements->pop();
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_TABLE_BODY
            );
        } elseif (($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'caption' || $tagName === 'col' ||
                $tagName === 'colgroup' || $tagName === 'tbody' ||
                $tagName === 'tfoot' || $tagName === 'thead' ||
                $tagName === 'tr')) ||
            ($tokenType == Token::END_TAG_TOKEN && $tagName === 'table')
        ) {
            // If the stack of open elements does not have a tr element in
            // table scope, this is a parse error; ignore the token.
            if (false) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table row context.
            $this->mOpenElements->clearBackToTableRowContext();

            // Pop the current node (which will be a tr element) from the stack
            // of open elements. Switch the insertion mode to "in table body".
            $this->mOpenElements->pop();
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_TABLE_BODY
            );

            // Reprocess the token.
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'tbody' ||
            $tagName === 'thead' || $tagName === 'tfoot')
        ) {
            // If the stack of open elements does not have an element in table
            // scope that is an HTML element with the same tag name as the
            // token, this is a parse error; ignore the token.
            if (false) {
                // Parse error.
                // Ignore the token.
            }

            // If the stack of open elements does not have a tr element in
            // table scope, ignore the token.
            if (false) {
                // Ignore the token.
                return;
            }

            // Clear the stack back to a table row context.
            $this->mOpenElements->clearBackToTableRowContext();

            // Pop the current node (which will be a tr element) from the stack
            // of open elements. Switch the insertion mode to "in table body".
            $this->mOpenElements->pop();
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE_BODY);

            // Reprocess the token.
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'body' ||
            $tagName === 'caption' || $tagName === 'col' ||
            $tagName === 'colgroup' || $tagName === 'html' ||
            $tagName === 'td' || $tagName === 'th')
        ) {
            // Parse error.
            // Ignore the token.
        } else {
            // Process the token using the rules for the "in table" insertion
            // mode.
            $this->inTableInsertionMode($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intd
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inCellInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'td' ||
            $tagName === 'th')
        ) {
            // If the stack of open elements does not have an element in table
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (false) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags();

            // Now, if the current node is not an HTML element with the same
            // tag name as the token, then this is a parse error.
            if (!$this->isHTMLElementWithName(
                $this->mOpenElements->top(),
                $tagName
            )) {
                // Parse error
            }

            // Pop elements from the stack of open elements stack until an
            // HTML element with the same tag name as the token has been popped
            // from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                $isValid = $this->isHTMLElementWithName(
                    $this->mOpenElements->pop(),
                    $tagName
                );

                if ($isValid) {
                    break;
                }
            };

            // Clear the list of active formatting elements up to the last
            // marker.
            $this->mActiveFormattingElements->clearUpToLastMarker();

            // Switch the insertion mode to "in row".
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_ROW);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'caption' || $tagName === 'col' ||
                $tagName === 'colgroup' || $tagName === 'tbody' ||
                $tagName === 'td' || $tagName === 'tfoot' ||
                $tagName === 'th' || $tagName === 'thead' || $tagName === 'tr')
        ) {
            // If the stack of open elements does not have a td or th element
            // in table scope, then this is a parse error; ignore the token.
            // (fragment case)
            if (false) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, close the cell and reprocess the token.
            $this->closeCell();
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'body' ||
            $tagName === 'caption' || $tagName === 'col' ||
            $tagName === 'colgroup' || $tagName === 'html')
        ) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::END_TAG_TOKEN && ($tagName === 'table' ||
            $tagName === 'tbody' || $tagName === 'tfoot' ||
            $tagName === 'thead' || $tagName === 'tr')
        ) {
            // If the stack of open elements does not have an element in table
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (false) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, close the cell and reprocess the token.
            $this->closeCell();
            $this->run($aToken);
        } else {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        }
    }

    /**
     * Performs the steps necessary to close a table cell (td) element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#close-the-cell
     */
    protected function closeCell()
    {
        // Generate implied end tags.
        $this->generateImpliedEndTags();

        // If the current node is not now a td element or a th element, then
        // this is a parse error.
        $currentNode = $this->mOpenElements->top();

        if (!($currentNode instanceof HTMLTableDataElement) &&
            !($currentNode instanceof HTMLTableHeaderCellElement)
        ) {
            // Parse error.
        }

        // Pop elements from the stack of open elements stack until a td
        // element or a th element has been popped from the stack.
        while (!$this->mOpenElements->isEmpty()) {
            $popped = $this->mOpenElements->pop();

            if ($popped instanceof HTMLTableDataElement ||
                $popped instanceof HTMLTableHeaderCellElement
            ) {
                break;
            }
        }

        // Clear the list of active formatting elements up to the last marker.
        $this->mActiveFormattingElements->clearUpToLastMarker();

        // Switch the insertion mode to "in row".
        $this->mParser->setInsertionMode(ParserInsertionMode::IN_ROW);

        // NOTE: The stack of open elements cannot have both a td and a th
        // element in table scope at the same time, nor can it have neither
        // when the close the cell algorithm is invoked.
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inselect
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inSelectInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN &&
            $aToken->data === "\x00"
        ) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::CHARACTER_TOKEN) {
            // Insert the token's character.
            $this->insertCharacter($aToken);
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'option'
        ) {
            // If the current node is an option element, pop that node from the
            // stack of open elements.
            if ($this->mOpenElements->top() instanceof HTMLOptionElement) {
                $this->mOpenElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'optgroup'
        ) {
            // If the current node is an option element, pop that node from the
            // stack of open elements.
            if ($this->mOpenElements->top() instanceof HTMLOptionElement) {
                $this->mOpenElements->pop();
            }

            // If the current node is an optgroup element, pop that node from
            // the stack of open elements.
            if ($this->mOpenElements->top() instanceof HTMLOptGroupElement) {
                $this->mOpenElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            $tagName === 'optgroup'
        ) {
            // First, if the current node is an option element, and the node
            // immediately before it in the stack of open elements is an
            // optgroup element, then pop the current node from the stack of
            // open elements.
            $this->mOpenElements->rewind();
            $this->mOpenElements->next();

            if ($this->mOpenElements->top() instanceof HTMLOptionElement &&
                $this->mOpenElements->current() instanceof HTMLOptGroupElement
            ) {
                $this->mOpenElements->pop();
            }

            // If the current node is an optgroup element, then pop that node
            // from the stack of open elements. Otherwise, this is a parse
            // error; ignore the token.
            if ($this->mOpenElements->top() instanceof HTMLOptGroupElement) {
                $this->mOpenElements->pop();
            } else {
                // Parse error.
                // Ignore the token.
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'option') {
            // If the current node is an option element, then pop that node
            // from the stack of open elements. Otherwise, this is a parse
            // error; ignore the token.
            if ($this->mOpenElements->top() instanceof HTMLOptionElement) {
                $this->mOpenElements->pop();
            } else {
                // Parse error.
                // Ignore the token.
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'select') {
            // If the stack of open elements does not have a select element in
            // select scope, this is a parse error; ignore the token. (fragment
            // case)
            if (!$this->mOpenElements->hasElementInSelectScope(
                'select',
                Namespaces::HTML
            )) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                if ($this->mOpenElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->mParser->resetInsertionMode();
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'select'
        ) {
            // Parse error
            // If the stack of open elements does not have a select
            // element in select scope, ignore the token. (fragment case)
            if (!$this->mOpenElements->hasElementInSelectScope(
                'select',
                Namespaces::HTML
            )) {
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                if ($this->mOpenElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->mParser->resetInsertionMode();
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'input' || $tagName === 'keygen' ||
                $tagName === 'textarea')
        ) {
            // Parse error
            // If the stack of open elements does not have a select
            // element in select scope, ignore the token. (fragment case)
            if (!$this->mOpenElements->hasElementInSelectScope(
                'select',
                Namespaces::HTML
            )) {
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                if ($this->mOpenElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->mParser->resetInsertionMode();

            // Reprocess the token.
            $this->run($aToken);
        } elseif (($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'script' || $tagName === 'template')) ||
            ($tokenType == Token::END_TAG_TOKEN && $tagName === 'template')
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } else {
            // Parse error.
            // Ignore the token.
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inselectintable
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inSelectInTableInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::START_TAG_TOKEN && ($tagName === 'caption' ||
            $tagName === 'table' || $tagName === 'tbody' ||
            $tagName === 'tfoot' || $tagName === 'thead' || $tagName === 'tr' ||
            $tagName === 'td' || $tagName === 'th')
        ) {
            // Parse error.
            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                if ($this->mOpenElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->mParser->resetInsertionMode();

            // Reprocess the token.
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            ($tagName === 'caption' || $tagName === 'table' ||
                $tagName === 'tbody' || $tagName === 'tfoot' ||
                $tagName === 'thead' || $tagName === 'tr' ||
                $tagName === 'td' || $tagName === 'th')
        ) {
            // Parse error
            // If the stack of open elements does not have an element in
            // table scope that is an HTML element with the same tag name as
            // that of the token, then ignore the token.
            if (!$this->mOpenElements->hasElementInTableScope(
                $tagName,
                Namespaces::HTML
            )) {
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                if ($this->mOpenElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->mParser->resetInsertionMode();

            // Reprocess the token.
            $this->run($aToken);
        } else {
            // Process the token using the rules for the "in select" insertion
            // mode.
            $this->inSelectInsertionMode($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intemplate
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inTemplateInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN ||
            $tokenType == Token::COMMENT_TOKEN ||
            $tokenType == Token::DOCTYPE_TOKEN
        ) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif (($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'base' || $tagName === 'basefont' ||
                $tagName === 'bgsound' || $tagName === 'link' ||
                $tagName === 'meta' || $tagName === 'noframes' ||
                $tagName === 'script' || $tagName === 'style' ||
                $tagName === 'template' || $tagName === 'title')) ||
            ($tokenType == Token::END_TAG_TOKEN && $tagName === 'template')
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            ($tagName === 'caption' || $tagName === 'colgroup' ||
                $tagName === 'tbody' || $tagName === 'tfoot' ||
                $tagName === 'thead')
        ) {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->mTemplateInsertionModes->pop();

            // Push "in table" onto the stack of template insertion modes so
            // that it is the new current template insertion mode.
            $this->mTemplateInsertionModes->push(ParserInsertionMode::IN_TABLE);

            // Switch the insertion mode to "in table", and reprocess the token.
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_TABLE);
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'col') {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->mTemplateInsertionModes->pop();

            // Push "in column group" onto the stack of template insertion modes
            // so that it is the new current template insertion mode.
            $this->mTemplateInsertionModes->push(
                ParserInsertionMode::IN_COLUMN_GROUP
            );

            // Switch the insertion mode to "in column group", and reprocess the
            // token.
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_COLUMN_GROUP
            );
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'tr') {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->mTemplateInsertionModes->pop();

            // Push "in table body" onto the stack of template insertion modes
            // so that it is the new current template insertion mode.
            $this->mTemplateInsertionModes->push(
                ParserInsertionMode::IN_TABLE_BODY
            );

            // Switch the insertion mode to "in table body", and reprocess the
            // token.
            $this->mParser->setInsertionMode(
                ParserInsertionMode::IN_TABLE_BODY
            );
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN && ($tagName === 'td' ||
            $tagName === 'th')
        ) {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->mTemplateInsertionModes->pop();

            // Push "in row" onto the stack of template insertion modes so that
            // it is the new current template insertion mode.
            $this->mTemplateInsertionModes->push(ParserInsertionMode::IN_ROW);

            // Switch the insertion mode to "in row", and reprocess the token.
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_ROW);
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN) {
            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->mTemplateInsertionModes->pop();

            // Push "in body" onto the stack of template insertion modes so that
            // it is the new current template insertion mode.
            $this->mTemplateInsertionModes->push(ParserInsertionMode::IN_BODY);

            // Switch the insertion mode to "in body", and reprocess the token.
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_BODY);
            $this->run($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // If there is no template element on the stack of open elements,
            // then stop parsing. (fragment case)
            if (!$this->mOpenElements->containsTemplateElement()) {
                $this->mParser->stopParsing();
            } else {
                // Parse  error
            }

            // Pop elements from the stack of open elements until a template
            // element has been popped from the stack.
            while (!$this->mOpenElements->isEmpty()) {
                $popped = $this->mOpenElements->pop();

                if ($popped instanceof HTMLTemplateElement) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $this->mActiveFormattingElements->clearUpToLastMarker();

            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $this->mTemplateInsertionModes->pop();

            // Reset the insertion mode appropriately.
            $this->mParser->resetInsertionMode();

            // Reprocess the token.
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-afterbody
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function afterBodyInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment as the last child of the first element in the
            // stack of open elements (the html element).
            $this->insertComment(
                $aToken,
                [$this->mOpenElements[0], 'beforeend']
            );
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'html') {
            // If the parser was originally created as part of the HTML fragment
            // parsing algorithm, this is a parse error; ignore the token.
            // (fragment case)
            if ($this->mParser->isFragmentCase()) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, switch the insertion mode to "after after body".
            $this->mParser->setInsertionMode(
                ParserInsertionMode::AFTER_AFTER_BODY
            );
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // Stop parsing.
            $this->mParser->stopParsing();
        } else {
            // Parse error.
            // Switch the insertion mode to "in body" and reprocess the token.
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_BODY);
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inframeset
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function inFramesetInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Insert the character.
            $this->insertCharacter($aToken);
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'frameset'
        ) {
            // Insert an HTML element for the token.
            $this->insertForeignElement($aToken, Namespaces::HTML);
        } elseif ($tokenType == Token::END_TAG_TOKEN &&
            $tagName === 'frameset'
        ) {
            // If the current node is the root html element, then this is a
            // parse error; ignore the token. (fragment case)
            if ($this->mOpenElements->top() instanceof HTMLHtmlElement) {
                // Parse error.
                // Ignore the token.
            } else {
                // Otherwise, pop the current node from the stack of open
                // elements.
                $this->mOpenElements->pop();
            }

            // If the parser was not originally created as part of the HTML
            // fragment parsing algorithm (fragment case), and the current node
            // is no longer a frameset element, then switch the insertion mode
            // to "after frameset".
            if (!$this->mParser->isFragmentCase() &&
                !($this->mOpenElements->top() instanceof HTMLFrameSetElement)
            ) {
                $this->mParser->setInsertionMode(
                    ParserInsertionMode::AFTER_FRAMESET
                );
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'frame'
        ) {
            // Insert an HTML element for the token. Immediately pop the current
            // node off the stack of open elements.
            $this->insertForeignElement($aToken, Namespaces::HTML);
            $this->mOpenElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($aToken->isSelfClosing()) {
                $aToken->acknowledge();
            }
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'noframes'
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // If the current node is not the root html element, then this is a
            // parse error.
            if (!($this->mOpenElements->top() instanceof HTMLHtmlElement)) {
                // Parse error.
            }

            // NOTE: The current node can only be the root html element in the
            // fragment case.

            // Stop parsing.
            $this->mParser->stopParsing();
        } else {
            // Parse error.
            // Ignore the token.
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-afterframeset
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function afterFramesetInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Insert the character.
            $this->insertCharacter($aToken);
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'html') {
            // Switch the insertion mode to "after after frameset".
            $this->mParser->setInsertionMode(
                ParserInsertionMode::AFTER_AFTER_FRAMESET
            );
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'noframes'
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // Stop parsing
            $this->mParser->stopParsing();
        } else {
            // Parse error.
            // Ignore the token.
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-after-body-insertion-mode
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function afterAfterBodyInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();

        if ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment as the last child of the Document object.
            $this->insertComment($aToken, [$this->mDocument, 'beforeend']);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN ||
            ($tokenType == Token::CHARACTER_TOKEN &&
                (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                    $data === "\x0C" || $data === "\x0D" || $data === "\x20")
            ) ||
            ($tokenType == Token::START_TAG_TOKEN &&
                $aToken->tagName === 'html')
        ) {
            // Process the token using the rules for the "in body" insertion mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // Stop parsing
            $this->mParser->stopParsing();
        } else {
            // Parse error.
            // Switch the insertion mode to "in body" and reprocess the token.
            $this->mParser->setInsertionMode(ParserInsertionMode::IN_BODY);
            $this->run($aToken);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-after-frameset-insertion-mode
     *
     * @param  Token  $aToken The token currently being processed.
     */
    protected function afterAfterFramesetInsertionMode(Token $aToken)
    {
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment as the last child of the Document object.
            $this->insertComment($aToken, [$this->mDocument, 'beforeend']);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN ||
            ($tokenType == Token::CHARACTER_TOKEN &&
                (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                    $data === "\x0C" || $data === "\x0D" || $data === "\x20")
            ) ||
            ($tokenType == Token::START_TAG_TOKEN && $tagName === 'html')
        ) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            $this->inBodyInsertionMode($aToken);
        } elseif ($tokenType == Token::EOF_TOKEN) {
            // Stop parsing.
            $this->mParser->stopParsing();
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            $tagName === 'noframes'
        ) {
            // Process the token using the rules for the "in head" insertion
            // mode.
            $this->inHeadInsertionMode($aToken);
        } else {
            // Parse error.
            // Ignore the token.
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inforeign
     * @param  Token  $aToken [description]
     * @return [type]         [description]
     */
    protected function inForeignContent(Token $aToken)
    {
        $fontTokenHasAttribute = false;
        $tokenType = $aToken->getType();
        $tagName = $aToken instanceof TagToken ? $aToken->tagName : '';

        if ($tokenType == Token::START_TAG_TOKEN && $tagName === 'font') {
            foreach ($aToken->attributes as $attr) {
                switch ($attr->name) {
                    case 'color':
                    case 'face':
                    case 'size':
                        $fontTokenHasAttribute = true;

                        break 2;
                }
            }
        }

        if ($tokenType == Token::CHARACTER_TOKEN &&
            $aToken->data === "\x00"
        ) {
            // Parse error.
            // Insert a U+FFFD REPLACEMENT CHARACTER character.
            $this->insertCharacter(EncodingUtils::mb_chr(0xFFFD));
        } elseif ($tokenType == Token::CHARACTER_TOKEN &&
            (($data = $aToken->data) === "\x09" || $data === "\x0A" ||
                $data === "\x0C" || $data === "\x0D" || $data === "\x20")
        ) {
            // Insert the token's character.
            $this->insertCharacter($aToken);
        } elseif ($tokenType == Token::CHARACTER_TOKEN) {
            // Insert the token's character.
            $this->insertCharacter($aToken);

            // Set the frameset-ok flag to "not ok".
            $this->mFramesetOk = 'not ok';
        } elseif ($tokenType == Token::COMMENT_TOKEN) {
            // Insert a comment.
            $this->insertComment($aToken);
        } elseif ($tokenType == Token::DOCTYPE_TOKEN) {
            // Parse error.
            // Ignore the token.
        } elseif ($tokenType == Token::START_TAG_TOKEN &&
            (preg_match(
                '/^(b|big|blockquote|body|br|center|code|dd|div|dl|dt|em|' .
                'embed|h[1-6]|head|hr|i|img|li|listing|menu|meta|nobr|ol|p|' .
                'pre|ruby|s|small|span|strong|strike|sub|sup|table|tt|u|ul|' .
                'var)$/',
                $tagName
            ) ||
            ($tokenType == Token::START_TAG_TOKEN && $tagName === 'font' &&
            $fontTokenHasAttribute))
        ) {
            // Parse error.

            // If the parser was originally created for the HTML fragment
            // parsing algorithm, then act as described in the "any other start
            // tag" entry below. (fragment case)
            if ($this->mParser->isFragmentCase()) {
                $adjustedCurrentNode = $this->mParser->getAdjustedCurrentNode();

                // If the adjusted current node is an element in the MathML
                // namespace, adjust MathML attributes for the token. (This
                // fixes the case of MathML attributes that are not all
                // lowercase.)
                if ($adjustedCurrentNode instanceof Element &&
                    $adjustedCurrentNode->namespaceURI === Namespaces::MATHML
                ) {
                    $this->adjustMathMLAttributes($aToken);
                }

                // If the adjusted current node is an element in the SVG
                // namespace, and the token's tag name is one of the ones in the
                // first column of the following table, change the tag name to
                // the name given in the corresponding cell in the second
                // column. (This fixes the case of SVG elements that are not all
                // lowercase.)
                $elementInSVGNamespace = $adjustedCurrentNode
                    instanceof Element &&
                    $adjustedCurrentNode->namespaceURI === Namespaces::SVG;

                if ($elementInSVGNamespace &&
                    self::SVG_ELEMENTS[$tagName] !== null
                ) {
                    $aToken->tagName = self::SVG_ELEMENTS[$tagName];
                }

                // If the adjusted current node is an element in the SVG
                // namespace, adjust SVG attributes for the token. (This fixes
                // the case of SVG attributes that are not all lowercase.)
                if ($elementInSVGNamespace) {
                    $this->adjustSVGAttributes($aToken);
                }

                // Adjust foreign attributes for the token. (This fixes the use
                // of namespaced attributes, in particular XLink in SVG.)
                $this->adjustForeignAttributes($aToken);

                // Insert a foreign element for the token, in the same namespace
                // as the adjusted current node.
                $this->insertForeignElement(
                    $aToken,
                    $adjustedCurrentNode->namespaceURI
                );

                if ($aToken->isSelfClosing()) {
                    $currentNode = $this->mOpenElements->top();

                    if ($tagName === 'script' &&
                        $currentNode instanceof Element &&
                        $currentNode->namespaceURI === Namespaces::SVG
                    ) {
                        // TODO: Acknowledge the token's self-closing flag, and
                        // then act as described in the steps for a "script" end
                        // tag below.
                        $aToken->acknowledge();
                    } else {
                        // Pop the current node off the stack of open elements
                        // and acknowledge the token's self-closing flag.
                        $this->mOpenElements->pop();
                        $aToken->acknowledge();
                    }
                }

                return;
            }

            // Pop an element from the stack of open elements, and then keep
            // popping more elements from the stack of open elements until the
            // current node is a MathML text integration point, an HTML
            // integration point, or an element in the HTML namespace.
            while (!$this->mOpenElements->isEmpty()) {
                $this->mOpenElements->pop();
                $currentNode = $this->mOpenElements->top();

                if ($this->isMathMLTextIntegrationPoint($currentNode) ||
                    $this->isHTMLIntegrationPoint($currentNode) ||
                    ($currentNode instanceof Element &&
                        $currentNode->namespaceURI === Namespaces::HTML)
                ) {
                    break;
                }
            }

            // Then, reprocess the token.
            $this->run($aToken);
        } elseif ($tokenType == Token::START_TAG_TOKEN) {
            $adjustedCurrentNode = $this->mParser->getAdjustedCurrentNode();

            // If the adjusted current node is an element in the MathML
            // namespace, adjust MathML attributes for the token. (This fixes
            // the case of MathML attributes that are not all lowercase.)
            if ($adjustedCurrentNode instanceof Element &&
                $adjustedCurrentNode->namespaceURI === Namespaces::MATHML
            ) {
                $this->adjustMathMLAttributes($aToken);
            }

            // If the adjusted current node is an element in the SVG namespace,
            // and the token's tag name is one of the ones in the first column
            // of the following table, change the tag name to the name given in
            // the corresponding cell in the second column. (This fixes the case
            // of SVG elements that are not all lowercase.)
            $elementInSVGNamespace =
                $adjustedCurrentNode instanceof Element &&
                $adjustedCurrentNode->namespaceURI === Namespaces::SVG;

            if ($elementInSVGNamespace &&
                self::SVG_ELEMENTS[$tagName] !== null
            ) {
                $aToken->tagName = self::SVG_ELEMENTS[$tagName];
            }

            // If the adjusted current node is an element in the SVG namespace,
            // adjust SVG attributes for the token. (This fixes the case of SVG
            // attributes that are not all lowercase.)
            if ($elementInSVGNamespace) {
                $this->adjustSVGAttributes($aToken);
            }

            // Adjust foreign attributes for the token. (This fixes the use of
            // namespaced attributes, in particular XLink in SVG.)
            $this->adjustForeignAttributes($aToken);

            // Insert a foreign element for the token, in the same namespace as
            // the adjusted current node.
            $this->insertForeignElement(
                $aToken,
                $adjustedCurrentNode->namespaceURI
            );

            if ($aToken->isSelfClosing()) {
                $currentNode = $this->mOpenElements->top();

                if ($tagName === 'script' &&
                    $currentNode instanceof Element &&
                    $currentNode->namespaceURI === Namespaces::SVG
                ) {
                    // TODO: Acknowledge the token's self-closing flag, and then
                    // act as described in the steps for a "script" end tag
                    // below.
                    $aToken->acknowledge();
                } else {
                    // Pop the current node off the stack of open elements and
                    // acknowledge the token's self-closing flag.
                    $this->mOpenElements->pop();
                    $aToken->acknowledge();
                }
            }
        } elseif ($tokenType == Token::END_TAG_TOKEN && $tagName === 'script' &&
            ($currentNode = $this->mOpenElements->top()) instanceof Element &&
            $currentNode->localName === 'script' &&
            $currentNode->namespaceURI === Namespaces::SVG
        ) {
            // Pop the current node off the stack of open elements.
            $this->mOpenElements->pop();

            // TODO: More stuff that will probably never be fully supported.
        } elseif ($tokenType == Token::END_TAG_TOKEN) {
            // Initialise node to be the current node (the bottommost node of
            // the stack).
            $node = $this->mOpenElements->top();

            // If node's tag name, converted to ASCII lowercase, is not the
            // same as the tag name of the token, then this is a parse error.
            if (Utils::toASCIILowercase($node->tagName) !== $tagName) {
                // Parse error.
            }

            $this->mOpenElements->rewind();

            // Step "Loop".
            while ($this->mOpenElements->valid()) {
                // If node is the topmost element in the stack of open elements,
                // abort these steps. (fragment case)
                if ($node === $this->mOpenElements[0]) {
                    return;
                }

                // If node's tag name, converted to ASCII lowercase, is the same
                // as the tag name of the token, pop elements from the stack of
                // open elements until node has been popped from the stack, and
                // then abort these steps.
                if (Utils::toASCIILowercase($node->tagName) === $tagName) {
                    while (!$this->mOpenElements->isEmpty()) {
                        if ($this->mOpenElements->pop() === $node) {
                            return;
                        }
                    }
                }

                // Set node to the previous entry in the stack of open elements.
                $this->mOpenElements->next();
                $node = $this->mOpenElements->current();

                // If node is not an element in the HTML namespace, return to
                // the step labeled loop.
                if (!($node instanceof Element &&
                    $node->namespaceURI === Namespaces::HTML)
                ) {

                } else {
                    // Otherwise, process the token according to the rules given
                    // in the section corresponding to the current insertion
                    // mode in HTML content.
                    $this->run($aToken);
                }
            }
        }
    }

    public function getTokenRepository()
    {
        return $this->mTokenRepository;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#tree-construction-dispatcher
     * @return [type] [description]
     */
    public function run(Token ...$aTokens)
    {
        foreach ($aTokens as $token) {
            $tokenType = $token->getType();

            if ($this->mOpenElements->isEmpty() ||
                (($adjustedCurrentNode =
                    $this->mParser->getAdjustedCurrentNode())
                    instanceof Element &&
                    $adjustedCurrentNode->namespaceURI === Namespaces::HTML) ||
                ($this->isMathMLTextIntegrationPoint($adjustedCurrentNode) &&
                    ($tokenType == Token::CHARACTER_TOKEN ||
                    ($tokenType == Token::START_TAG_TOKEN &&
                        (($tagName = $token->tagName) != 'mglyph' &&
                            $tagName != 'malignmark')))
                ) ||
                ($adjustedCurrentNode instanceof Element &&
                    $adjustedCurrentNode->namespaceURI === Namespaces::MATHML &&
                    $adjustedCurrentNode->localName === 'annotaion-xml') ||
                ($this->isHTMLIntegrationPoint($adjustedCurrentNode) &&
                    ($tokenType == Token::START_TAG_TOKEN ||
                        $tokenType == Token::CHARACTER_TOKEN)
                ) ||
                $tokenType == Token::EOF_TOKEN
            ) {
                switch ($this->mParser->getInsertionMode()) {
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
            } else {
                $this->inForeignContent($token);
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adjust-foreign-attributes
     * @param  TagToken $aToken [description]
     * @return [type]           [description]
     */
    protected function adjustForeignAttributes(TagToken $aToken)
    {
        foreach ($aToken->attributes as $attr) {
            $name = $attr->name;

            if (preg_match(
                '/^(xlink):(actuate|arcrole|href|role|show|title|type)$/',
                $name,
                $matches
            )) {
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
     * @param  TagToken $aToken [description]
     * @return [type]           [description]
     */
    protected function adjustMathMLAttributes(TagToken $aToken)
    {
        foreach ($aToken->attributes as $attr) {
            if ($attr->name === 'definitionurl') {
                $attr->name = 'definitionURL';
                break;
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adjust-svg-attributes
     * @param  TagToken $aToken [description]
     * @return [type]           [description]
     */
    protected function adjustSVGAttributes(TagToken $aToken)
    {
        foreach ($aToken->attributes as $attr) {
            $name = $attr->name;

            if (self::SVG_ATTRIBUTES[$name] !== null) {
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
     * @param Token $aToken The token currently being processed.
     *
     * @param string $aNamespace The namespace of the element that is to be
     *     created.
     *
     * @param Node $aIntendedParent The parent not which the newely created node
     *     will be inserted in to.
     *
     * @return Node
     */
    protected function createElementForToken(
        Token $aToken,
        $aNamespace,
        Node $aIntendedParent
    ) {
        $document = $aIntendedParent->getNodeDocument();
        $localName = $aToken->tagName;
        $element = ElementFactory::create($document, $localName, $aNamespace);
        $attributes = $element->getAttributeList();

        // Append each attribute in the given token to element
        foreach ($aToken->attributes as $attr) {
            $a = new Attr(
                $attr->name,
                $attr->value,
                $attr->namespace,
                $attr->prefix
            );
            $a->setNodeDocument($document);
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
        if ($element instanceof HTMLInputElement ||
            $element instanceof HTMLKeygenElement ||
            $element instanceof HTMLOutputElement ||
            $element instanceof HTMLSelectElement ||
            $element instanceof HTMLTextAreaElement
        ) {
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
        if ($element instanceof HTMLButtonElement ||
            $element instanceof HTMLFieldSetElement ||
            $element instanceof HTMLInputElement ||
            $element instanceof HTMLKeygenElement ||
            $element instanceof HTMLObjectElement ||
            $element instanceof HTMLOutputElement ||
            $element instanceof HTMLSelectElement ||
            $element instanceof HTMLTextAreaElement ||
            $element instanceof HTMLImageElement
        ) {
            // TODO
        }

        return $element;
    }



    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generate-all-implied-end-tags-thoroughly
     */
    protected function generateAllImpliedEndTagsThoroughly()
    {
        $pattern = '/^(caption|colgroup|dd|dt|li|optgroup|option|p|rb|rp|rt';
        $pattern .= '|rtc|tbody|td|tfoot|th|thead|tr)$/';
        $currentNode = $this->mOpenElements->top();

        while (!$this->mOpenElements->isEmpty() &&
            $currentNode instanceof HTMLElement &&
            preg_match($pattern, $currentNode->localName)
        ) {
            $this->mOpenElements->pop();
            $currentNode = $this->mOpenElements->top();
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generate-implied-end-tags
     * @param  string $aExcluded [description]
     */
    protected function generateImpliedEndTags($aExcluded = '')
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
            'rtc'      => 0
        ];

        $currentNode = $this->mOpenElements->top();

        if ($aExcluded) {
            unset($tags[$aExcluded]);
        }

        while (!$this->mOpenElements->isEmpty() &&
            isset($tags[$currentNode->localName])
        ) {
            $this->mOpenElements->pop();
            $currentNode = $this->mOpenElements->top();
        }
    }

    /**
     * Gets the appropriate place to insert the node.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#appropriate-place-for-inserting-a-node
     *
     * @param Node|null $aOverrideTarget Optional argument. When given, it
     *     overrides the target insertion point for the node. Default value is
     *     null.
     *
     * @return array The first index contains the node where another node will
     *     be inserted. The second index contains where, relative to the node
     *     in the first index that, the node to be inserted will be inserted.
     */
    protected function getAppropriatePlaceForInsertingNode(
        Node $aOverrideTarget = null
    ) {
        // If there was an override target specified, then let target be the
        // override target. Otherwise, let target be the current node.
        $target = $aOverrideTarget ?: $this->mOpenElements->top();

        // NOTE: Foster parenting happens when content is misnested in tables.
        if ($this->mFosterParenting && ($target instanceof HTMLTableElement ||
            $target instanceof HTMLTableSectionElement ||
            $target instanceof HTMLTableRowElement)
        ) {
            $lastTemplate = null;
            $lastTable = null;
            $lastTableIndex = 0;
            $lastTemplateIndex = 0;
            $abortSubSteps = false;

            foreach ($this->mOpenElements as $key => $element) {
                if ($element instanceof HTMLTemplateElement &&
                    $lastTemplate === null
                ) {
                    $lastTemplate = $element;
                    $lastTemplateIndex = $key;

                    if ($lastTable) {
                        break;
                    }
                } elseif ($element instanceof HTMLTableElement &&
                    $lastTable === null
                ) {
                    $lastTable = $element;
                    $lastTableIndex = $key;

                    if ($lastTemplate) {
                        break;
                    }
                }
            }

            $templateLowerThanTable = ($lastTemplate !== null &&
                $lastTable === null) || ($lastTemplate && $lastTable &&
                $lastTemplateIndex > $lastTableIndex);

            if ($lastTemplate &&
                ($lastTable === null || ($lastTable && $templateLowerThanTable))
            ) {
                $adjustedInsertionLocation = [
                    $lastTemplate->content,
                    'beforeend'
                ];
                $abortSubSteps = true;
            } elseif ($lastTable === null) {
                // Fragment case
                $adjustedInsertionLocation = [
                    $this->mOpenElements[0],
                    'beforeend'
                ];
                $abortSubSteps = true;
            } elseif ($lastTable->parentNode) {
                $adjustedInsertionLocation = [$lastTable, 'beforebegin'];
                $abortSubSteps = true;
            }

            if (!$abortSubSteps) {
                $previousElement = $this->mOpenElements[$lastTableIndex - 1];
                $adjustedInsertionLocation = [$previousElement, 'beforeend'];
            }
        } else {
            $adjustedInsertionLocation = [$target, 'beforeend'];
        }

        if ($adjustedInsertionLocation[0] instanceof HTMLTemplateElement &&
            ($adjustedInsertionLocation[1] === 'beforeend' ||
            $adjustedInsertionLocation[1] === 'afterbegin')
        ) {
            $adjustedInsertionLocation = [
                $adjustedInsertionLocation[0]->content,
                'beforeend'
            ];
        }

        return $adjustedInsertionLocation;
    }

    /**
     * Inserts a sequence of characters in to a preexisting text node or creates
     * a new text node if one does not exist in the expected insertion location.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#insert-a-character
     *
     * @param CharacterToken|string $aData A token that contains character data
     *     or A literal string of characters to insert instead of data from a
     *     token.
     *
     * @param string $aCharacters
     */
    protected function insertCharacter($aData)
    {
        // Let data be the characters passed to the algorithm, or, if no
        // characters were explicitly specified, the character of the character
        // token being processed.
        $data = is_string($aData) ? $aData : $aData->data;

        // Let the adjusted insertion location be the appropriate place for
        // inserting a node.
        $adjustedInsertionLocation =
            $this->getAppropriatePlaceForInsertingNode();

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
            $node->data .= $data;
            return;
        }

        $node = new Text($data);
        $node->setNodeDocument(
            $adjustedInsertionLocation[0]->getNodeDocument()
        );
        $this->insertNode($node, $adjustedInsertionLocation);
    }

    /**
     * Inserts a comment node in to the document while processing a comment
     * token.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#insert-a-comment
     *
     * @param CommentToken $aToken The comment token being processed.
     *
     * @param array $aPosition The position where the comment should be
     *     inserted.
     */
    protected function insertComment(
        CommentToken $aToken,
        array $aPosition = null
    ) {
        // Let data be the data given in the comment token being processed.
        $data = $aToken->data;

        // If position was specified, then let the adjusted insertion location
        // be position. Otherwise, let adjusted insertion location be the
        // appropriate place for inserting a node.
        if ($aPosition !== null) {
            $adjustedInsertionLocation = $aPosition;
        } else {
            $adjustedInsertionLocation =
                $this->getAppropriatePlaceForInsertingNode();
        }

        // Create a Comment node whose data attribute is set to data and whose
        // node document is the same as that of the node in which the adjusted
        // insertion location finds itself.
        $ownerDocument = $adjustedInsertionLocation[0]->getNodeDocument();
        $node = new Comment($data);
        $node->setNodeDocument($ownerDocument);

        // Insert the newly created node at the adjusted insertion location.
        $this->insertNode($node, $adjustedInsertionLocation);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#insert-a-foreign-element
     *
     * @param TagToken The start or end tag token that will be used to create a
     *     new element.
     *
     * @param string $aNamespace The namespace that the created element will
     *     reside in.
     *
     * @return Element|null The newly created element or void if the element
     *     could not be inserted into the intended location.
     */
    protected function insertForeignElement(TagToken $aToken, $aNamespace)
    {
        // Let the adjusted insertion location be the appropriate place for
        // inserting a node.
        $adjustedInsertionLocation =
            $this->getAppropriatePlaceForInsertingNode();

        // Create an element for the token in the given namespace, with the
        // intended parent being the element in which the adjusted insertion
        // location finds itself.
        $node = $this->createElementForToken(
            $aToken,
            $aNamespace,
            $adjustedInsertionLocation[0]
        );

        // If it is possible to insert an element at the adjusted insertion
        // location, then insert the newly created element at the adjusted
        // insertion location.
        //
        // NOTE: If the adjusted insertion location cannot accept more elements,
        // e.g. because it's a Document that already has an element child, then
        // the newly created element is dropped on the floor.
        try {
            $this->insertNode($node, $adjustedInsertionLocation);
        } catch (DOMException $e) {
            return null;
        }

        // Push the element onto the stack of open elements so that it is the
        // new current node.
        $this->mOpenElements->push($node);
        $this->mTokenRepository->attach($node, $aToken);

        // Return the newly created element.
        return $node;
    }

    /**
     * Inserts a node based at a specific location. It follows similar rules to
     * Element's insertAdjacentHTML method.
     *
     * @param Node $aNode The node that is being inserted in to the document.
     *
     * @param array $aPosition The position at which the node is to be inserted.
     */
    protected function insertNode(Node $aNode, array $aPosition)
    {
        $relativeNode = $aPosition[0];
        $position = $aPosition[1];

        switch ($position) {
            case 'beforebegin':
                $relativeNode->parentNode->insertNode($aNode, $relativeNode);

                break;

            case 'afterbegin':
                $relativeNode->insertNode($aNode, $relativeNode->firstChild);

                break;

            case 'beforeend':
                $relativeNode->appendChild($aNode);

                break;

            case 'afterend':
                $relativeNode->parentNode->insertNode(
                    $aNode,
                    $relativeNode->nextSibling
                );
        }
    }

    protected function isHTMLElementWithName(Node $aNode, $aLocalName)
    {
        return $aNode instanceof Element &&
            $aNode->namespaceURI === Namespaces::HTML &&
            $aNode->localName === $aLocalName;
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
     *
     * @param Node $aNode [description]
     *
     * @return bool
     */
    protected function isHTMLIntegrationPoint(Node $aNode)
    {
        if ($aNode instanceof Element) {
            $localName = $aNode->localName;
            $namespace = $aNode->namespaceURI;
            $token = $this->mTokenRepository[$aNode];

            if ($localName === 'annotaion-xml' &&
                $namespace === Namespaces::MATHML
            ) {
                foreach ($token->attributes as $attr) {
                    if ($attr->name === 'encoding') {
                        $value = $attr->value;

                        if (strcasecmp($value, 'text/html') === 0 ||
                            strcasecmp($value, 'application/xhtml+xml') === 0
                        ) {
                            return true;
                        }
                    }
                }
            }

            if (($localName === 'foreignObject' &&
                    $namespace === Namespaces::SVG) ||
                ($localName === 'desc' && $namespace === Namespaces::SVG) ||
                ($localName === 'title' && $namespace === Namespaces::SVG)
            ) {
                return true;
            }
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
     *
     * @param Node $aNode [description]
     *
     * @return bool
     */
    protected function isMathMLTextIntegrationPoint(Node $aNode)
    {
        if ($aNode instanceof Element &&
            $aNode->namespaceURI === Namespaces::MATHML
        ) {
            switch ($aNode->localName) {
                case 'mi':
                case 'mo':
                case 'mn':
                case 'ms':
                case 'mtext':
                    return true;
            }
        }

        return false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#special
     * @param  Node    $aNode [description]
     * @return boolean        [description]
     */
    protected function isSpecialNode(Node $aNode)
    {
        if ($aNode instanceof Element) {
            $namespace = $aNode->namespaceURI;

            if ($namespace === Namespaces::HTML) {
                switch ($aNode->localName) {
                    case 'address':
                    case 'applet':
                    case 'area':
                    case 'article':
                    case 'aside':
                    case 'base':
                    case 'basefont':
                    case 'bgsound':
                    case 'blockquote':
                    case 'body':
                    case 'br':
                    case 'button':
                    case 'caption':
                    case 'center':
                    case 'col':
                    case 'colgroup':
                    case 'dd':
                    case 'details':
                    case 'dir':
                    case 'div':
                    case 'dl':
                    case 'dt':
                    case 'embed':
                    case 'fieldset':
                    case 'figcaption':
                    case 'figure':
                    case 'footer':
                    case 'form':
                    case 'frame':
                    case 'frameset':
                    case 'h1':
                    case 'h2':
                    case 'h3':
                    case 'h4':
                    case 'h5':
                    case 'h6':
                    case 'head':
                    case 'header':
                    case 'hgroup':
                    case 'hr':
                    case 'html':
                    case 'iframe':
                    case 'img':
                    case 'input':
                    case 'keygen':
                    case 'li':
                    case 'link':
                    case 'listing':
                    case 'main':
                    case 'marquee':
                    case 'menu':
                    case 'meta':
                    case 'nav':
                    case 'noembed':
                    case 'noframes':
                    case 'noscript':
                    case 'object':
                    case 'ol':
                    case 'p':
                    case 'param':
                    case 'plaintext':
                    case 'pre':
                    case 'script':
                    case 'section':
                    case 'select':
                    case 'source':
                    case 'style':
                    case 'summary':
                    case 'table':
                    case 'tbody':
                    case 'td':
                    case 'template':
                    case 'textarea':
                    case 'tfoot':
                    case 'th':
                    case 'thead':
                    case 'title':
                    case 'tr':
                    case 'track':
                    case 'ul':
                    case 'wbr':
                    case 'xmp':
                        return true;
                }
            } elseif ($namespace === Namespaces::MATHML) {
                switch ($aNode->localName) {
                    case 'mi':
                    case 'mo':
                    case 'mn':
                    case 'ms':
                    case 'mtext':
                    case 'annotation-xml':
                        return true;
                }
            } elseif ($namespace === Namespaces::SVG) {
                switch ($aNode->localName) {
                    case 'foreignObject':
                    case 'desc':
                    case 'title':
                        return true;
                }
            }
        }

        return false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generic-raw-text-element-parsing-algorithm
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generic-rcdata-element-parsing-algorithm
     * @param  [type] $aToken     [description]
     * @param  [type] $aAlgorithm [description]
     * @return [type]             [description]
     */
    protected function parseGenericTextElement(Token $aToken, $aAlgorithm)
    {
        // Insert an HTML element for the token.
        $node = $this->insertForeignElement($aToken, Namespaces::HTML);

        // If the algorithm that was invoked is the generic raw text element
        // parsing algorithm, switch the tokenizer to the RAWTEXT state;
        // otherwise the algorithm invoked was the generic RCDATA element
        // parsing algorithm, switch the tokenizer to the RCDATA state.
        if ($aAlgorithm == self::RAW_TEXT_ELEMENT_ALGORITHM) {
            $this->mParser->setTokenizerState(TokenizerState::RAWTEXT);
        } else {
            $this->mParser->setTokenizerState(TokenizerState::RCDATA);
        }

        // Let the original insertion mode be the current insertion mode.
        $this->mParser->setOriginalInsertionMode(
            $this->mParser->getInsertionMode()
        );

        // Then, switch the insertion mode to "text".
        $this->mParser->setInsertionMode(ParserInsertionMode::TEXT);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#reconstruct-the-active-formatting-elements
     */
    public function reconstructActiveFormattingElements()
    {
        // If there are no entries in the list of active formatting elements,
        // then there is nothing to reconstruct; stop this algorithm.
        if ($this->mActiveFormattingElements->isEmpty()) {
            return;
        }

        // If the last (most recently added) entry in the list of active
        // formatting elements is a marker, or if it is an element that is in
        // the stack of open elements, then there is nothing to reconstruct;
        // stop this algorithm.
        $entry = $this->mActiveFormattingElements->top();

        if ($entry instanceof Marker ||
            $this->mOpenElements->contains($entry)
        ) {
            return;
        }

        $cursor = count($this->mActiveFormattingElements) - 1;

        // If there are no entries before entry in the list of active formatting
        // elements, then jump to the step labeled create.
        Rewind:
        if ($cursor === 0) {
            goto Create;
        }

        // Let entry be the entry one earlier than entry in the list of active
        // formatting elements.
        $entry = $this->mActiveFormattingElements[--$cursor];

        // If entry is neither a marker nor an element that is also in the stack
        // of open elements, go to the step labeled rewind.
        if (!($entry instanceof Marker) &&
            !$this->mOpenElements->contains($entry)
        ) {
            goto Rewind;
        }

        Advance:
        // Let entry be the element one later than entry in the list of active
        // formatting elements.
        $entry = $this->mActiveFormattingElements[++$cursor];

        Create:
        // Insert an HTML element for the token for which the element entry was
        // created, to obtain new element.
        $newElement = $this->insertForeignElement(
            $this->mTokenRepository[$entry],
            Namespaces::HTML
        );

        // Replace the entry for entry in the list with an entry for new
        // element.
        $this->mActiveFormattingElements->replace($newElement, $entry);

        // If the entry for new element in the list of active formatting
        // elements is not the last entry in the list, return to the step
        // labeled advance.
        if ($newElement !== $this->mActiveFormattingElements->top()) {
            goto Advance;
        }
    }
}
