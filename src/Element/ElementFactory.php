<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\Document;
use Rowbot\DOM\Element\HTML\HTMLAnchorElement;
use Rowbot\DOM\Element\HTML\HTMLAreaElement;
use Rowbot\DOM\Element\HTML\HTMLAudioElement;
use Rowbot\DOM\Element\HTML\HTMLBaseElement;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLBRElement;
use Rowbot\DOM\Element\HTML\HTMLButtonElement;
use Rowbot\DOM\Element\HTML\HTMLCanvasElement;
use Rowbot\DOM\Element\HTML\HTMLDataElement;
use Rowbot\DOM\Element\HTML\HTMLDataListElement;
use Rowbot\DOM\Element\HTML\HTMLDetailsElement;
use Rowbot\DOM\Element\HTML\HTMLDialogElement;
use Rowbot\DOM\Element\HTML\HTMLDirectoryElement;
use Rowbot\DOM\Element\HTML\HTMLDivElement;
use Rowbot\DOM\Element\HTML\HTMLDListElement;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLEmbedElement;
use Rowbot\DOM\Element\HTML\HTMLFieldSetElement;
use Rowbot\DOM\Element\HTML\HTMLFontElement;
use Rowbot\DOM\Element\HTML\HTMLFormElement;
use Rowbot\DOM\Element\HTML\HTMLFrameElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHeadingElement;
use Rowbot\DOM\Element\HTML\HTMLHRElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLIFrameElement;
use Rowbot\DOM\Element\HTML\HTMLImageElement;
use Rowbot\DOM\Element\HTML\HTMLInputElement;
use Rowbot\DOM\Element\HTML\HTMLLabelElement;
use Rowbot\DOM\Element\HTML\HTMLLegendElement;
use Rowbot\DOM\Element\HTML\HTMLLIElement;
use Rowbot\DOM\Element\HTML\HTMLLinkElement;
use Rowbot\DOM\Element\HTML\HTMLMapElement;
use Rowbot\DOM\Element\HTML\HTMLMarqueeElement;
use Rowbot\DOM\Element\HTML\HTMLMenuElement;
use Rowbot\DOM\Element\HTML\HTMLMenuItemElement;
use Rowbot\DOM\Element\HTML\HTMLMetaElement;
use Rowbot\DOM\Element\HTML\HTMLMeterElement;
use Rowbot\DOM\Element\HTML\HTMLModElement;
use Rowbot\DOM\Element\HTML\HTMLObjectElement;
use Rowbot\DOM\Element\HTML\HTMLOListElement;
use Rowbot\DOM\Element\HTML\HTMLOptGroupElement;
use Rowbot\DOM\Element\HTML\HTMLOptionElement;
use Rowbot\DOM\Element\HTML\HTMLOutputElement;
use Rowbot\DOM\Element\HTML\HTMLParagraphElement;
use Rowbot\DOM\Element\HTML\HTMLParamElement;
use Rowbot\DOM\Element\HTML\HTMLPictureElement;
use Rowbot\DOM\Element\HTML\HTMLPreElement;
use Rowbot\DOM\Element\HTML\HTMLProgressElement;
use Rowbot\DOM\Element\HTML\HTMLQuoteElement;
use Rowbot\DOM\Element\HTML\HTMLScriptElement;
use Rowbot\DOM\Element\HTML\HTMLSelectElement;
use Rowbot\DOM\Element\HTML\HTMLSlotElement;
use Rowbot\DOM\Element\HTML\HTMLSourceElement;
use Rowbot\DOM\Element\HTML\HTMLSpanElement;
use Rowbot\DOM\Element\HTML\HTMLStyleElement;
use Rowbot\DOM\Element\HTML\HTMLTableCaptionElement;
use Rowbot\DOM\Element\HTML\HTMLTableCellElement;
use Rowbot\DOM\Element\HTML\HTMLTableColElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Element\HTML\HTMLTextAreaElement;
use Rowbot\DOM\Element\HTML\HTMLTimeElement;
use Rowbot\DOM\Element\HTML\HTMLTitleElement;
use Rowbot\DOM\Element\HTML\HTMLTrackElement;
use Rowbot\DOM\Element\HTML\HTMLUListElement;
use Rowbot\DOM\Element\HTML\HTMLUnknownElement;
use Rowbot\DOM\Element\HTML\HTMLVideoElement;
use Rowbot\DOM\Element\SVG\SVGAElement;
use Rowbot\DOM\Element\SVG\SVGAnimateElement;
use Rowbot\DOM\Element\SVG\SVGAnimateMotionElement;
use Rowbot\DOM\Element\SVG\SVGAnimateTransformElement;
use Rowbot\DOM\Element\SVG\SVGCircleElement;
use Rowbot\DOM\Element\SVG\SVGClipPathElement;
use Rowbot\DOM\Element\SVG\SVGDefsElement;
use Rowbot\DOM\Element\SVG\SVGDescElement;
use Rowbot\DOM\Element\SVG\SVGElement;
use Rowbot\DOM\Element\SVG\SVGFEBlendElement;
use Rowbot\DOM\Element\SVG\SVGFEColorMatrixElement;
use Rowbot\DOM\Element\SVG\SVGFEComponentTransferElement;
use Rowbot\DOM\Element\SVG\SVGFECompositeElement;
use Rowbot\DOM\Element\SVG\SVGFEConvolveMatrixElement;
use Rowbot\DOM\Element\SVG\SVGFEDiffuseLightingElement;
use Rowbot\DOM\Element\SVG\SVGFEDisplacementMapElement;
use Rowbot\DOM\Element\SVG\SVGFEDistantLightElement;
use Rowbot\DOM\Element\SVG\SVGFEDropShadowElement;
use Rowbot\DOM\Element\SVG\SVGFEFloodElement;
use Rowbot\DOM\Element\SVG\SVGFEFuncAElement;
use Rowbot\DOM\Element\SVG\SVGFEFuncBElement;
use Rowbot\DOM\Element\SVG\SVGFEFuncGElement;
use Rowbot\DOM\Element\SVG\SVGFEFuncRElement;
use Rowbot\DOM\Element\SVG\SVGFEGaussianBlurElement;
use Rowbot\DOM\Element\SVG\SVGFEImageElement;
use Rowbot\DOM\Element\SVG\SVGFEMergeElement;
use Rowbot\DOM\Element\SVG\SVGFEMergeNodeElement;
use Rowbot\DOM\Element\SVG\SVGFEMorphologyElement;
use Rowbot\DOM\Element\SVG\SVGFEOffsetElement;
use Rowbot\DOM\Element\SVG\SVGFEPointLightElement;
use Rowbot\DOM\Element\SVG\SVGFESpecularLightingElement;
use Rowbot\DOM\Element\SVG\SVGFESpotLightElement;
use Rowbot\DOM\Element\SVG\SVGFETileElement;
use Rowbot\DOM\Element\SVG\SVGFETurbulenceElement;
use Rowbot\DOM\Element\SVG\SVGFilterElement;
use Rowbot\DOM\Element\SVG\SVGForeignObjectElement;
use Rowbot\DOM\Element\SVG\SVGGElement;
use Rowbot\DOM\Element\SVG\SVGImageElement;
use Rowbot\DOM\Element\SVG\SVGLinearGradientElement;
use Rowbot\DOM\Element\SVG\SVGLineElement;
use Rowbot\DOM\Element\SVG\SVGMarkerElement;
use Rowbot\DOM\Element\SVG\SVGMaskElement;
use Rowbot\DOM\Element\SVG\SVGMetadataElement;
use Rowbot\DOM\Element\SVG\SVGMPathElement;
use Rowbot\DOM\Element\SVG\SVGPathElement;
use Rowbot\DOM\Element\SVG\SVGPatternElement;
use Rowbot\DOM\Element\SVG\SVGPolygonElement;
use Rowbot\DOM\Element\SVG\SVGPolylineElement;
use Rowbot\DOM\Element\SVG\SVGRadialGradientElement;
use Rowbot\DOM\Element\SVG\SVGRectElement;
use Rowbot\DOM\Element\SVG\SVGScriptElement;
use Rowbot\DOM\Element\SVG\SVGSetElement;
use Rowbot\DOM\Element\SVG\SVGStopElement;
use Rowbot\DOM\Element\SVG\SVGStyleElement;
use Rowbot\DOM\Element\SVG\SVGSVGElement;
use Rowbot\DOM\Element\SVG\SVGSwitchElement;
use Rowbot\DOM\Element\SVG\SVGSymbolElement;
use Rowbot\DOM\Element\SVG\SVGTextElement;
use Rowbot\DOM\Element\SVG\SVGTextPathElement;
use Rowbot\DOM\Element\SVG\SVGTitleElement;
use Rowbot\DOM\Element\SVG\SVGTSpanElement;
use Rowbot\DOM\Element\SVG\SVGUseElement;
use Rowbot\DOM\Element\SVG\SVGViewElement;
use Rowbot\DOM\Namespaces;

final class ElementFactory
{
    private const HTML_ELEMENTS = [
        'a'          => HTMLAnchorElement::class,
        'abbr'       => HTMLElement::class,
        'acronym'    => HTMLElement::class, // Obsolete
        'address'    => HTMLElement::class,
        'area'       => HTMLAreaElement::class,
        'article'    => HTMLElement::class,
        'aside'      => HTMLElement::class,
        'audio'      => HTMLAudioElement::class,
        'b'          => HTMLElement::class,
        'base'       => HTMLBaseElement::class,
        'basefont'   => HTMLElement::class, // Obsolete
        'bdi'        => HTMLElement::class,
        'bdo'        => HTMLElement::class,
        'big'        => HTMLElement::class, // Obsolete
        'blockquote' => HTMLQuoteElement::class,
        'body'       => HTMLBodyElement::class,
        'br'         => HTMLBRElement::class,
        'button'     => HTMLButtonElement::class,
        'canvas'     => HTMLCanvasElement::class,
        'caption'    => HTMLTableCaptionElement::class,
        'center'     => HTMLElement::class, // Obsolete
        'cite'       => HTMLElement::class,
        'code'       => HTMLElement::class,
        'col'        => HTMLTableColElement::class,
        'colgroup'   => HTMLTableColElement::class,
        'data'       => HTMLDataElement::class,
        'datalist'   => HTMLDataListElement::class,
        'dd'         => HTMLElement::class,
        'del'        => HTMLModElement::class,
        'details'    => HTMLDetailsElement::class,
        'dfn'        => HTMLElement::class,
        'dialog'     => HTMLDialogElement::class,
        'dir'        => HTMLDirectoryElement::class, // Obsolete
        'div'        => HTMLDivElement::class,
        'dl'         => HTMLDListElement::class,
        'dt'         => HTMLElement::class,
        'em'         => HTMLElement::class,
        'embed'      => HTMLEmbedElement::class,
        'fieldset'   => HTMLFieldSetElement::class,
        'figcaption' => HTMLElement::class,
        'figure'     => HTMLElement::class,
        'font'       => HTMLFontElement::class, // Obsolete
        'footer'     => HTMLElement::class,
        'form'       => HTMLFormElement::class,
        'frame'      => HTMLFrameElement::class, // Deprecated
        'frameset'   => HTMLFrameSetElement::class, // Deprecated
        'h1'         => HTMLHeadingElement::class,
        'h2'         => HTMLHeadingElement::class,
        'h3'         => HTMLHeadingElement::class,
        'h4'         => HTMLHeadingElement::class,
        'h5'         => HTMLHeadingElement::class,
        'h6'         => HTMLHeadingElement::class,
        'head'       => HTMLHeadElement::class,
        'header'     => HTMLElement::class,
        'hgroup'     => HTMLElement::class, // Obsolete
        'hr'         => HTMLHRElement::class,
        'html'       => HTMLHtmlElement::class,
        'i'          => HTMLElement::class,
        'iframe'     => HTMLIFrameElement::class,
        'img'        => HTMLImageElement::class,
        'input'      => HTMLInputElement::class,
        'ins'        => HTMLModElement::class,
        'kbd'        => HTMLElement::class,
        'label'      => HTMLLabelElement::class,
        'legend'     => HTMLLegendElement::class,
        'li'         => HTMLLIElement::class,
        'link'       => HTMLLinkElement::class,
        'main'       => HTMLElement::class,
        'map'        => HTMLMapElement::class,
        'mark'       => HTMLElement::class,
        'marquee'    => HTMLMarqueeElement::class, // Obsolete
        'menu'       => HTMLMenuElement::class,
        'menuitem'   => HTMLMenuItemElement::class,
        'meta'       => HTMLMetaElement::class,
        'meter'      => HTMLMeterElement::class,
        'nav'        => HTMLElement::class,
        'noembed'    => HTMLElement::class, // Deprecated and non-standard
        'noframes'   => HTMLElement::class,
        'noscript'   => HTMLElement::class,
        'object'     => HTMLObjectElement::class,
        'ol'         => HTMLOListElement::class,
        'optgroup'   => HTMLOptGroupElement::class,
        'option'     => HTMLOptionElement::class,
        'output'     => HTMLOutputElement::class,
        'p'          => HTMLParagraphElement::class,
        'param'      => HTMLParamElement::class,
        'picture'    => HTMLPictureElement::class,
        'plaintext'  => HTMLElement::class, // Obsolete
        'pre'        => HTMLPreElement::class,
        'progress'   => HTMLProgressElement::class,
        'q'          => HTMLQuoteElement::class,
        'rp'         => HTMLElement::class,
        'rt'         => HTMLElement::class,
        'rtc'        => HTMLElement::class,
        'ruby'       => HTMLElement::class,
        's'          => HTMLElement::class,
        'samp'       => HTMLElement::class,
        'script'     => HTMLScriptElement::class,
        'section'    => HTMLElement::class,
        'select'     => HTMLSelectElement::class,
        'slot'       => HTMLSlotElement::class,
        'small'      => HTMLElement::class,
        'source'     => HTMLSourceElement::class,
        'span'       => HTMLSpanElement::class,
        'strike'     => HTMLElement::class, // Obsolete
        'strong'     => HTMLElement::class,
        'style'      => HTMLStyleElement::class,
        'sub'        => HTMLElement::class,
        'summary'    => HTMLElement::class,
        'sup'        => HTMLElement::class,
        'table'      => HTMLTableElement::class,
        'tbody'      => HTMLTableSectionElement::class,
        'td'         => HTMLTableCellElement::class,
        'template'   => HTMLTemplateElement::class,
        'textarea'   => HTMLTextAreaElement::class,
        'tfoot'      => HTMLTableSectionElement::class,
        'th'         => HTMLTableCellElement::class,
        'thead'      => HTMLTableSectionElement::class,
        'time'       => HTMLTimeElement::class,
        'title'      => HTMLTitleElement::class,
        'tr'         => HTMLTableRowElement::class,
        'track'      => HTMLTrackElement::class,
        'tt'         => HTMLElement::class, // Obsolete
        'u'          => HTMLElement::class,
        'ul'         => HTMLUListElement::class,
        'var'        => HTMLElement::class,
        'video'      => HTMLVideoElement::class,
        'wbr'        => HTMLElement::class,
        'xmp'        => HTMLElement::class, // Obsolete
    ];

    /**
     * @see https://svgwg.org/svg2-draft/eltindex.html
     */
    private const SVG_ELEMENTS = [
        'a'                   => SVGAElement::class,
        'animate'             => SVGAnimateElement::class,
        'animateMotion'       => SVGAnimateMotionElement::class,
        'animateTransform'    => SVGAnimateTransformElement::class,
        'circle'              => SVGCircleElement::class,
        'clipPath'            => SVGClipPathElement::class,
        'defs'                => SVGDefsElement::class,
        'desc'                => SVGDescElement::class,
        'feBlend'             => SVGFEBlendElement::class,
        'feColorMatrix'       => SVGFEColorMatrixElement::class,
        'feComponentTransfer' => SVGFEComponentTransferElement::class,
        'feComposite'         => SVGFECompositeElement::class,
        'feConvolveMatrix'    => SVGFEConvolveMatrixElement::class,
        'feDiffuseLighting'   => SVGFEDiffuseLightingElement::class,
        'feDisplacementMap'   => SVGFEDisplacementMapElement::class,
        'feDistantLight'      => SVGFEDistantLightElement::class,
        'feDropShadow'        => SVGFEDropShadowElement::class,
        'feFlood'             => SVGFEFloodElement::class,
        'feFuncA'             => SVGFEFuncAElement::class,
        'feFuncB'             => SVGFEFuncBElement::class,
        'feFuncG'             => SVGFEFuncGElement::class,
        'feFuncR'             => SVGFEFuncRElement::class,
        'feGaussianBlur'      => SVGFEGaussianBlurElement::class,
        'feImage'             => SVGFEImageElement::class,
        'feMerge'             => SVGFEMergeElement::class,
        'feMergeNode'         => SVGFEMergeNodeElement::class,
        'feMorphology'        => SVGFEMorphologyElement::class,
        'feOffset'            => SVGFEOffsetElement::class,
        'fePointLight'        => SVGFEPointLightElement::class,
        'feSpecularLighting'  => SVGFESpecularLightingElement::class,
        'feSpotLight'         => SVGFESpotLightElement::class,
        'feTile'              => SVGFETileElement::class,
        'feTurbulence'        => SVGFETurbulenceElement::class,
        'filter'              => SVGFilterElement::class,
        'foreignObject'       => SVGForeignObjectElement::class,
        'g'                   => SVGGElement::class,
        'image'               => SVGImageElement::class,
        'line'                => SVGLineElement::class,
        'linearGradient'      => SVGLinearGradientElement::class,
        'marker'              => SVGMarkerElement::class,
        'mask'                => SVGMaskElement::class,
        'metadata'            => SVGMetadataElement::class,
        'mpath'               => SVGMPathElement::class,
        'path'                => SVGPathElement::class,
        'pattern'             => SVGPatternElement::class,
        'polygon'             => SVGPolygonElement::class,
        'polyline'            => SVGPolylineElement::class,
        'radialGradient'      => SVGRadialGradientElement::class,
        'rect'                => SVGRectElement::class,
        'script'              => SVGScriptElement::class,
        'set'                 => SVGSetElement::class,
        'stop'                => SVGStopElement::class,
        'style'               => SVGStyleElement::class,
        'svg'                 => SVGSVGElement::class,
        'switch'              => SVGSwitchElement::class,
        'symbol'              => SVGSymbolElement::class,
        'text'                => SVGTextElement::class,
        'textPath'            => SVGTextPathElement::class,
        'title'               => SVGTitleElement::class,
        'tspan'               => SVGTSpanElement::class,
        'use'                 => SVGUseElement::class,
        'view'                => SVGViewElement::class,
    ];

    /**
     * @codeCoverageIgnore
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
     */
    public static function create(
        Document $document,
        string $localName,
        ?string $namespace,
        ?string $prefix = null
    ): Element {
        $interface = Element::class;

        if ($namespace === Namespaces::HTML) {
            $interface = self::HTML_ELEMENTS[$localName] ?? HTMLUnknownElement::class;
        } elseif ($namespace === Namespaces::SVG) {
            $interface = self::SVG_ELEMENTS[$localName] ?? SVGElement::class;
        }

        return new $interface($document, $localName, $namespace, $prefix);
    }

    /**
     * Creates an element in a given namespace.
     *
     * @see https://dom.spec.whatwg.org/#internal-createelementns-steps
     *
     * @param \Rowbot\DOM\Document $document      The Element's owner document.
     * @param ?string              $namespace     The Element's namespace.
     * @param string               $qualifiedName The Element's fully qualified name.
     */
    public static function createNS(
        Document $document,
        ?string $namespace,
        string $qualifiedName
    ): Element {
        [$namespace, $prefix, $localName] = Namespaces::validateAndExtract(
            $namespace,
            $qualifiedName
        );

        return self::create($document, $localName, $namespace, $prefix);
    }
}
