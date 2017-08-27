<?php
namespace Rowbot\DOM\Element;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Exception\DOMException;

abstract class ElementFactory
{
    /**
     * @see https://dom.spec.whatwg.org/#concept-create-element
     *
     * @param Document $aDocument The element's owner document.
     *
     * @param string $aLocalName The element's local name that you are creating.
     *
     * @param string $aNamespace The namespace that the element belongs to.
     *
     * @param string|null $aPrefix Optional. The namespace prefix of the
     *     element.
     *
     * @return Element
     */
    public static function create(
        $aDocument,
        $aLocalName,
        $aNamespace,
        $aPrefix = null
    ) {
        switch ($aNamespace) {
            case Namespaces::HTML:
                $interface = self::getHTMLInterfaceFor($aLocalName);

                break;

            default:
                $interface = 'Rowbot\\DOM\\Element\\Element';
        }

        return $interface::create(
            $aDocument,
            $aLocalName,
            $aNamespace,
            $aPrefix
        );
    }

    /**
     * @see https://dom.spec.whatwg.org/#internal-createelementns-steps
     *
     * @param Document $aDocument The Element's owner document.
     *
     * @param string $aNamespace The Element's namespace.
     *
     * @param string $aQualifiedName The Element's fully qualified name.
     *
     * @return Element
     */
    public static function createNS($aDocument, $aNamespace, $aQualifiedName)
    {
        try {
            list(
                $namespace,
                $prefix,
                $localName
            ) = Namespaces::validateAndExtract(
                $aNamespace,
                $aQualifiedName
            );
        } catch (DOMException $e) {
            throw $e;
        }

        try {
            $element = self::create(
                $aDocument,
                $localName,
                $namespace,
                $prefix
            );
        } catch (DOMException $e) {
            throw $e;
        }

        return $element;
    }

    public static function getHTMLInterfaceFor($aLocalName)
    {
        switch ($aLocalName) {
            /**
             * These are elements whose tag name differs
             * from its DOM interface name, so map the tag
             * name to the interface name.
             */
            case 'a':
                $interfaceName = 'Anchor';

                break;

            case 'br':
                $interfaceName = 'BR';

                break;

            case 'datalist':
                $interfaceName = 'DataList';

                break;

            case 'dl':
                $interfaceName = 'DList';

                break;

            case 'fieldset':
                $interfaceName = 'FieldSet';

                break;

            case 'frameset':
                $interfaceName = 'FrameSet';

                break;

            case 'hr':
                $interfaceName = 'HR';

                break;

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $interfaceName = 'Heading';

                break;

            case 'iframe':
                $interfaceName = 'IFrame';

                break;

            case 'img':
                $interfaceName = 'Image';

                break;

            case 'ins':
            case 'del':
                $interfaceName = 'Mod';

                break;

            case 'li':
                $interfaceName = 'LI';

                break;

            case 'menuitem':
                $interfaceName = 'MenuItem';

                break;

            case 'ol':
                $interfaceName = 'OList';

                break;

            case 'optgroup':
                $interfaceName = 'OptGroup';

                break;

            case 'p':
                $interfaceName = 'Paragraph';

                break;

            case 'blockquote':
            case 'cite':
            case 'q':
                $interfaceName = 'Quote';

                break;

            case 'caption':
                $interfaceName = 'TableCaption';

                break;

            case 'td':
                $interfaceName = 'TableDataCell';

                break;

            case 'th':
                $interfaceName = 'TableHeaderCell';

                break;

            case 'col':
            case 'colgroup':
                $interfaceName = 'TableCol';

                break;

            case 'tr':
                $interfaceName = 'TableRow';

                break;

            case 'tbody':
            case 'thead':
            case 'tfoot':
                $interfaceName = 'TableSection';

                break;

            case 'textarea':
                $interfaceName = 'TextArea';

                break;

            case 'ul':
                $interfaceName = 'UList';

                break;

            /**
             * These are known HTML elements that don't have their
             * own DOM interface, but should not be classified as
             * HTMLUnknownElements.
             */
            case 'abbr':
            case 'address':
            case 'article':
            case 'aside':
            case 'b':
            case 'bdi':
            case 'bdo':
            case 'cite':
            case 'code':
            case 'dd':
            case 'dfn':
            case 'dt':
            case 'em':
            case 'figcaption':
            case 'figure':
            case 'footer':
            case 'header':
            case 'hgroup':
            case 'i':
            case 'kbd':
            case 'main':
            case 'mark':
            case 'nav':
            case 'noscript':
            case 'rp':
            case 'rt':
            case 'rtc':
            case 'ruby':
            case 's':
            case 'samp':
            case 'section':
            case 'small':
            case 'strong':
            case 'sub':
            case 'summary':
            case 'sup':
            case 'u':
            case 'var':
            case 'wbr':
                $interfaceName = '';

                break;

            /**
             * These are known HTML elements that have their own
             * DOM interface and their names do not differ from
             * their interface names.
             */
            case 'area':
            case 'audio':
            case 'base':
            case 'body':
            case 'button':
            case 'canvas':
            case 'content':
            case 'data':
            case 'details':
            case 'dialog':
            case 'div':
            case 'embed':
            case 'form':
            case 'head':
            case 'html':
            case 'input':
            case 'label':
            case 'legend':
            case 'link':
            case 'map':
            case 'menu':
            case 'meta':
            case 'meter':
            case 'object':
            case 'option':
            case 'output':
            case 'param':
            case 'picture':
            case 'pre':
            case 'progress':
            case 'script':
            case 'select':
            case 'source':
            case 'span':
            case 'style':
            case 'table':
            case 'template':
            case 'time':
            case 'title':
            case 'track':
            case 'video':
                $interfaceName = ucfirst($aLocalName);

                break;

            default:
                $interfaceName = 'Unknown';
        }

        return 'Rowbot\\DOM\\Element\\HTML\\HTML' . $interfaceName . 'Element';
    }
}
