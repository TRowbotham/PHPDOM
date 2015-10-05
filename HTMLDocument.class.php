<?php
require_once 'Document.class.php';

/**
 * HTMLDocument represents an HTML document.
 *
 * @property HTMLBodyElement    $body   Represents the HTML document's <body> element.
 *
 * @property HTMLHeadElement    $head   Represents the HTML document's <head> element.
 *
 * @property string             $title  Reflects the text content of the <title> element.
 */
class HTMLDocument extends Document {
    public function __construct() {
        parent::__construct();

        $this->mContentType = 'text/html';
        $this->mDoctype = $this->implementation->createDocumentType('html', '', '');
        $documentElement = $this->createElement('html');
        $head = $this->createElement('head');
        $head->appendChild($this->createElement('title'));
        $documentElement->appendChild($head);
        $documentElement->appendChild($this->createElement('body'));
        $this->appendChild($this->mDoctype);
        $this->appendChild($documentElement);
    }

    public function __get( $aName ) {
        switch ($aName) {
            case 'body':
                $node = $this->documentElement ? $this->documentElement->firstChild : null;

                while ($node) {
                    if ($node instanceof HTMLBodyElement || $node instanceof HTMLFrameSetElement) {
                        break;
                    }

                    $node = $node->nextSibling;
                }

                return $node;

            case 'head':
                $node = $this->documentElement ? $this->documentElement->firstChild : null;

                while ($node) {
                    if ($node instanceof HTMLHeadElement) {
                        break;
                    }

                    $node = $node->nextSibling;
                }

                return $node;

            case 'title':
                $root = self::_getRootElement($this);

                if ($root instanceof SVGElement && $root->namespaceURI === Namespaces::SVG) {
                    $nodeFilter = function($aNode) {
                        return $aNode instanceof SVGTitleElement ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
                    };
                } else {
                    $nodeFilter = function($aNode) {
                        return $aNode instanceof HTMLTitleElement ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
                    };
                }

                $value = '';
                $tw = new TreeWalker($this, NodeFilter::SHOW_ELEMENT, $nodeFilter);
                $title = $tw->nextNode();

                foreach ($title->childNodes as $node) {
                    if ($node instanceof Text) {
                        $value .= $node->data;
                    }
                }

                return $value;
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'body':
                if (!($aValue instanceof HTMLBodyElement) && !($aValue instanceof HTMLFrameSetElement)) {
                    throw new HierarchyRequestError;
                    return;
                }

                $currentBody = $this->body;

                if ($aValue === $currentBody) {
                    return;
                }

                if ($currentBody) {
                    $this->replaceChild($aValue, $currentBody);
                    return;
                }

                $root = $this->documentElement;

                if (!$root) {
                    throw new HierarchyRequestError;
                    return;
                }

                $root->appendChild($aValue);

                break;

            case 'title':
                if (!is_string($aValue)) {
                    return;
                }

                $root = self::_getRootElement($this);

                if ($root instanceof SVGElement && $root->namespaceURI === Namespaces::SVG) {
                    $element = $root->firstChild;

                    while ($element) {
                        if ($element instanceof SVGTitleElement) {
                            break;
                        }

                        $element = $element->nextSibling;
                    }

                    if (!$element) {
                        // TODO: Create title element with SVG namespace
                    }

                    $element->textContent = $aValue;
                } else if ($root && $root->namespaceURI === Namespaces::HTML) {
                    $tw = new TreeWalker($root, NodeFilter::SHOW_ELEMENT, function($aNode) {
                        return $aNode instanceof HTMLTitleElement ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
                    });
                    $element = $tw->nextNode();

                    if (!$element && !$this->head) {
                        return;
                    }

                    if (!$element) {
                        $element = $this->head->appendChild($this->createElement('title'));
                    }

                    $element->textContent = $aValue;
                }

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    /**
     * Creates an HTMLElement with the specified tag name.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-createelement
     *
     * @param  string       $aLocalName   The name of the element to create.
     *
     * @return HTMLElement                A known HTMLElement or HTMLUnknownElement.
     */
    public function createElement($aLocalName) {
        // TODO: Make sure localName matches the name production

        $localName = strtolower($aLocalName);

        switch($localName) {
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
            case 'hrgroup':
            case 'i':
            case 'kbd':
            case 'main':
            case 'mark':
            case 'nav':
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
            case 'data':
            case 'div':
            case 'embed':
            case 'form':
            case 'head':
            case 'html':
            case 'input':
            case 'keygen':
            case 'label':
            case 'legend':
            case 'link':
            case 'map':
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
            case 'time':
            case 'title':
            case 'track':
            case 'video':
                $interfaceName = ucfirst($localName);

                break;

            default:
                $interfaceName = 'Unknown';
        }

        $className = 'HTML' . $interfaceName . 'Element';
        require_once 'HTMLElement/' . $className . '.class.php';

        $node = new $className($localName);
        $node->mNamespaceURI = Namespaces::HTML;
        $node->mOwnerDocument = $this;

        return $node;
    }

    public function toHTML() {
        $html = '';

        foreach($this->mChildNodes as $child) {
            $html .= $child->toHTML();
        }

        return $html;
    }

    public function __toString() {
        return get_class($this);
    }
}
