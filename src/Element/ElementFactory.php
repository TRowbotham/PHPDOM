<?php
declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\Document;
use Rowbot\DOM\Namespaces;

final class ElementFactory
{
    private const HTML_ELEMENTS = [
        'a'          => 'HTMLAnchorElement',
        'abbr'       => 'HTMLElement',
        'acronym'    => 'HTMLElement', // Obsolete
        'address'    => 'HTMLElement',
        'area'       => 'HTMLAreaElement',
        'article'    => 'HTMLElement',
        'aside'      => 'HTMLElement',
        'audio'      => 'HTMLAudioElement',
        'b'          => 'HTMLElement',
        'base'       => 'HTMLBaseElement',
        'basefont'   => 'HTMLElement', // Obsolete
        'bdi'        => 'HTMLElement',
        'bdo'        => 'HTMLElement',
        'big'        => 'HTMLElement', // Obsolete
        'blockquote' => 'HTMLQuoteElement',
        'body'       => 'HTMLBodyElement',
        'br'         => 'HTMLBRElement',
        'button'     => 'HTMLButtonElement',
        'canvas'     => 'HTMLCanvasElement',
        'caption'    => 'HTMLTableCaptionElement',
        'center'     => 'HTMLElement', // Obsolete
        'cite'       => 'HTMLElement',
        'code'       => 'HTMLElement',
        'col'        => 'HTMLTableColElement',
        'colgroup'   => 'HTMLTableColElement',
        'data'       => 'HTMLDataElement',
        'datalist'   => 'HTMLDataListElement',
        'dd'         => 'HTMLElement',
        'del'        => 'HTMLModElement',
        'details'    => 'HTMLDetailsElement',
        'dfn'        => 'HTMLElement',
        'dialog'     => 'HTMLDialogElement',
        'dir'        => 'HTMLDirectoryElement', // Obsolete
        'div'        => 'HTMLDivElement',
        'dl'         => 'HTMLDListElement',
        'dt'         => 'HTMLElement',
        'em'         => 'HTMLElement',
        'embed'      => 'HTMLEmbedElement',
        'fieldset'   => 'HTMLFieldSetElement',
        'figcaption' => 'HTMLElement',
        'figure'     => 'HTMLElement',
        'font'       => 'HTMLFontElement', // Obsolete
        'footer'     => 'HTMLElement',
        'form'       => 'HTMLFormElement',
        'frame'      => 'HTMLFrameElement', // Deprecated
        'frameset'   => 'HTMLFrameSetElement', // Deprecated
        'h1'         => 'HTMLHeadingElement',
        'h2'         => 'HTMLHeadingElement',
        'h3'         => 'HTMLHeadingElement',
        'h4'         => 'HTMLHeadingElement',
        'h5'         => 'HTMLHeadingElement',
        'h6'         => 'HTMLHeadingElement',
        'head'       => 'HTMLHeadElement',
        'header'     => 'HTMLElement',
        'hgroup'     => 'HTMLElement', // Obsolete
        'hr'         => 'HTMLHRElement',
        'html'       => 'HTMLHtmlElement',
        'i'          => 'HTMLElement',
        'iframe'     => 'HTMLIFrameElement',
        'img'        => 'HTMLImageElement',
        'input'      => 'HTMLInputElement',
        'ins'        => 'HTMLModElement',
        'kbd'        => 'HTMLElement',
        'label'      => 'HTMLLabelElement',
        'legend'     => 'HTMLLegendElement',
        'li'         => 'HTMLLIElement',
        'link'       => 'HTMLLinkElement',
        'main'       => 'HTMLElement',
        'map'        => 'HTMLMapElement',
        'mark'       => 'HTMLElement',
        'marquee'    => 'HTMLMarqueeElement', // Obsolete
        'menu'       => 'HTMLMenuElement',
        'menuitem'   => 'HTMLMenuItemElement',
        'meta'       => 'HTMLMetaElement',
        'meter'      => 'HTMLMeterElement',
        'nav'        => 'HTMLElement',
        'noembed'    => 'HTMLElement', // Deprecated and non-standard
        'noframes'   => 'HTMLElement',
        'noscript'   => 'HTMLElement',
        'object'     => 'HTMLObjectElement',
        'ol'         => 'HTMLOListElement',
        'optgroup'   => 'HTMLOptGroupElement',
        'option'     => 'HTMLOptionElement',
        'output'     => 'HTMLOutputElement',
        'p'          => 'HTMLParagraphElement',
        'param'      => 'HTMLParamElement',
        'picture'    => 'HTMLPictureElement',
        'pre'        => 'HTMLPreElement',
        'progress'   => 'HTMLProgressElement',
        'q'          => 'HTMLQuoteElement',
        'rp'         => 'HTMLElement',
        'rt'         => 'HTMLElement',
        'rtc'        => 'HTMLElement',
        'ruby'       => 'HTMLElement',
        's'          => 'HTMLElement',
        'samp'       => 'HTMLElement',
        'script'     => 'HTMLScriptElement',
        'section'    => 'HTMLElement',
        'select'     => 'HTMLSelectElement',
        'small'      => 'HTMLElement',
        'source'     => 'HTMLSourceElement',
        'span'       => 'HTMLSpanElement',
        'strike'     => 'HTMLElement', // Obsolete
        'strong'     => 'HTMLElement',
        'style'      => 'HTMLStyleElement',
        'sub'        => 'HTMLElement',
        'summary'    => 'HTMLElement',
        'sup'        => 'HTMLElement',
        'table'      => 'HTMLTableElement',
        'tbody'      => 'HTMLTableSectionElement',
        'td'         => 'HTMLTableCellElement',
        'template'   => 'HTMLTemplateElement',
        'textarea'   => 'HTMLTextAreaElement',
        'tfoot'      => 'HTMLTableSectionElement',
        'th'         => 'HTMLTableCellElement',
        'thead'      => 'HTMLTableSectionElement',
        'time'       => 'HTMLTimeElement',
        'title'      => 'HTMLTitleElement',
        'tr'         => 'HTMLTableRowElement',
        'track'      => 'HTMLTrackElement',
        'tt'         => 'HTMLElement', // Obsolete
        'u'          => 'HTMLElement',
        'ul'         => 'HTMLUListElement',
        'var'        => 'HTMLElement',
        'video'      => 'HTMLVideoElement',
        'wbr'        => 'HTMLElement',
        'xmp'        => 'HTMLElement' // Obsolete
    ];

    /**
     * @see https://svgwg.org/svg2-draft/eltindex.html
     */
    private const SVG_ELEMENTS = [
        'a'                   => 'SVGAElement',
        'animate'             => 'SVGAnimateElement',
        'animateMotion'       => 'SVGAnimateMotionElement',
        'animateTransform'    => 'SVGAnimateTransformElement',
        'circle'              => 'SVGCircleElement',
        'clipPath'            => 'SVGClipPathElement',
        'defs'                => 'SVGDefsElement',
        'desc'                => 'SVGDescElement',
        'feBlend'             => 'SVGFEBlendElement',
        'feColorMatrix'       => 'SVGFEColorMatrixElement',
        'feComponentTransfer' => 'SVGFEComponentTransferElement',
        'feComposite'         => 'SVGFECompositeElement',
        'feConvolveMatrix'    => 'SVGFEConvolveMatrix',
        'feDiffuseLighting'   => 'SVGFEDiffuseLightingElement',
        'feDisplacementMap'   => 'SVGFEDisplacementMapElement',
        'feDistantLight'      => 'SVGFEDistantLightElement',
        'feDropShadow'        => 'SVGFEDropShadowElement',
        'feFlood'             => 'SVGFEFloodElement',
        'feFuncA'             => 'SVGFEFuncAElement',
        'feFuncB'             => 'SVGFEFuncBElement',
        'feFuncG'             => 'SVGFEFuncGElement',
        'feFuncR'             => 'SVGFEFuncRElement',
        'feGaussianBlur'      => 'SVGFEGaussianBlurElement',
        'feImage'             => 'SVGFEImageElement',
        'feMerge'             => 'SVGFEMergeElement',
        'feMergeNode'         => 'SVGFEMergeNodeElement',
        'feMorphology'        => 'SVGFEMorphologyElement',
        'feOffset'            => 'SVGFEOffsetElement',
        'fePointLight'        => 'SVGFEPointLightElement',
        'feSpecularLighting'  => 'SVGFESpecularLightingElement',
        'feSpotLight'         => 'SVGFESpotLightElement',
        'feTile'              => 'SVGFETileElement',
        'feTurbulence'        => 'SVGFETurbulenceElement',
        'filter'              => 'SVGFilterElement',
        'foreignObject'       => 'SVGForeignObjectElement',
        'g'                   => 'SVGGElement',
        'image'               => 'SVGImageElement',
        'line'                => 'SVGLineElement',
        'linearGradient'      => 'SVGLinearGradientElement',
        'marker'              => 'SVGMarkerElement',
        'mask'                => 'SVGMaskElement',
        'metadata'            => 'SVGMetadataElement',
        'mpath'               => 'SVGMPathElement',
        'path'                => 'SVGPathElement',
        'pattern'             => 'SVGPatternElement',
        'polygon'             => 'SVGPolygonElement',
        'polyline'            => 'SVGPolylineElement',
        'radialGradient'      => 'SVGRadialGradientElement',
        'rect'                => 'SVGRectElement',
        'script'              => 'SVGScriptElement',
        'set'                 => 'SVGSetElement',
        'stop'                => 'SVGStopElement',
        'style'               => 'SVGStyleElement',
        'svg'                 => 'SVGSVGElement',
        'switch'              => 'SVGSwitchElement',
        'symbol'              => 'SVGSymbolElement',
        'text'                => 'SVGTextElement',
        'textPath'            => 'SVGTextPathElement',
        'title'               => 'SVGTitleElement',
        'tspan'               => 'SVGTSpanElement',
        'use'                 => 'SVGUseElement',
        'view'                => 'SVGViewElement'
    ];

    /**
     * Constructor.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Creates an element.
     *
     * @see https://dom.spec.whatwg.org/#concept-create-element
     *
     * @param \Rowbot\DOM\Document $document  The element's owner document.
     * @param string               $localName The element's local name that you are creating.
     * @param ?string              $namespace The namespace that the element belongs to.
     * @param ?string              $prefix    (optional) The namespace prefix of the element.
     *
     * @return \Rowbot\DOM\Element\Element
     */
    public static function create(
        Document $document,
        string $localName,
        ?string $namespace,
        ?string $prefix = null
    ): Element {
        $interface = 'Element';

        if ($namespace === Namespaces::HTML) {
            $interface = 'HTML\\HTMLUnknownElement';

            if (isset(self::HTML_ELEMENTS[$localName])) {
                $interface = 'HTML\\' . self::HTML_ELEMENTS[$localName];
            }
        } elseif ($namespace === Namespaces::SVG) {
            $interface = 'SVG\\SVGElement';

            if (isset(self::SVG_ELEMENTS[$localName])) {
                $interface = 'SVG\\' . self::SVG_ELEMENTS[$localName];
            }
        }

        return ('\\Rowbot\\DOM\\Element\\' . $interface)::create(
            $document,
            $localName,
            $namespace,
            $prefix
        );
    }

    /**
     * Creates an element in a given namespace.
     *
     * @see https://dom.spec.whatwg.org/#internal-createelementns-steps
     *
     * @param \Rowbot\DOM\Document $document      The Element's owner document.
     * @param ?string              $namespace     The Element's namespace.
     * @param string               $qualifiedName The Element's fully qualified name.
     *
     * @return \Rowbot\DOM\Element\Element
     */
    public static function createNS(
        Document $document,
        ?string $namespace,
        string $qualifiedName
    ): Element {
        list(
            $namespace,
            $prefix,
            $localName
        ) = Namespaces::validateAndExtract(
            $namespace,
            $qualifiedName
        );

        return self::create(
            $document,
            $localName,
            $namespace,
            $prefix
        );
    }
}
