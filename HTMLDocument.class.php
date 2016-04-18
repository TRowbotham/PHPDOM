<?php
namespace phpjs;

use phpjs\elements\html\HTMLBodyElement;
use phpjs\elements\html\HTMLFrameSetElement;
use phpjs\elements\html\HTMLHeadElement;
use phpjs\elements\html\HTMLHtmlElement;
use phpjs\elements\html\HTMLTitleElement;
use phpjs\elements\svg\SVGSVGElement;
use phpjs\elements\svg\SVGTitleElement;
use phpjs\exceptions\HierarchyRequestError;

/**
 * HTMLDocument represents an HTML document.
 *
 * @property HTMLBodyElement $body Represents the HTML document's <body>
 *     element.
 *
 * @property HTMLHeadElement $head Represents the HTML document's <head>
 *     element.
 *
 * @property string $title  Reflects the text content of the <title> element.
 */
class HTMLDocument extends Document
{
    public function __construct()
    {
        parent::__construct();

        $this->mContentType = 'text/html';
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'body':
                return $this->getBodyElement();

            case 'head':
                return $this->getHeadElement();

            case 'title':
                return $this->getTitle();

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'body':
                if (
                    !($aValue instanceof HTMLBodyElement) &&
                    !($aValue instanceof HTMLFrameSetElement)
                ) {
                    throw new HierarchyRequestError();
                    return;
                }

                $currentBody = $this->body;

                if ($aValue === $currentBody) {
                    return;
                }

                if ($currentBody) {
                    $this->replaceNode($aValue, $currentBody);
                    return;
                }

                $root = $this->documentElement;

                if (!$root) {
                    throw new HierarchyRequestError();
                    return;
                }

                $root->appendChild($aValue);

                break;

            case 'title':
                if (!is_string($aValue)) {
                    return;
                }

                $root = self::_getRootElement($this);

                if (
                    $root instanceof SVGElement &&
                    $root->namespaceURI === Namespaces::SVG
                ) {
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
                } elseif ($root && $root->namespaceURI === Namespaces::HTML) {
                    $tw = new TreeWalker(
                        $root,
                        NodeFilter::SHOW_ELEMENT,
                        function ($aNode) {
                            return $aNode instanceof HTMLTitleElement ?
                                NodeFilter::FILTER_ACCEPT :
                                NodeFilter::FILTER_SKIP;
                        }
                    );
                    $element = $tw->nextNode();

                    if (!$element && !$this->head) {
                        return;
                    }

                    if (!$element) {
                        $element = $this->head->appendChild(
                            $this->createElement('title')
                        );
                    }

                    $element->textContent = $aValue;
                }

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function toHTML()
    {
        $html = '';

        foreach($this->mChildNodes as $child) {
            $html .= $child->toHTML();
        }

        return $html;
    }

    public function __toString()
    {
        return get_class($this);
    }

    /**
     * Gets the document's body element. The document's body element is the
     * first child of the html element that is either a body or frameset
     * element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#dom-document-body
     *
     * @return HTMLElement|null
     */
    protected function getBodyElement()
    {
        $docElement = $this->getFirstElementChild();

        if ($docElement && $docElement instanceof HTMLHtmlElement) {
            // Get the first element in the document element that is a body or
            // frameset element.
            foreach ($docElement->mChildNodes as $child) {
                $isValidBody = $child instanceof HTMLBodyElement ||
                    $child instanceof HTMLFrameSetElement;

                if ($isValidBody) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * Gets the document's head element. The document's head element is the
     * first child of the html element that is a head element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#dom-document-head
     *
     * @return HTMLHeadElement|null
     */
    protected function getHeadElement()
    {
        $docElement = $this->getFirstElementChild();

        if ($docElement && $docElement instanceof HTMLHtmlElement) {
            // Get the first child in the document element that is a head
            // element.
            foreach ($docElement->mChildNodes as $child) {
                if ($child instanceof HTMLHeadElement) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * Gets the text of the document's title element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#document.title
     *
     * @return string
     */
    protected function getTitle()
    {
        $element = $this->getTitleElement();
        $value = '';

        if ($element) {
            // Concatenate the text data of all the text node children of the
            // title element.
            foreach ($element->mChildNodes as $child) {
                if ($child instanceof Text) {
                    $value .= $child->data;
                }
            }
        }

        // Trim whitespace and replace consecutive whitespace with a single
        // space.
        if (!empty($value)) {
            return preg_replace(
                ['/^\s+/', '/\s+$/', '/\s+/'],
                ['', '', '\x{0020}'],
                $value
            );
        }

        return $value;
    }

    /**
     * Gets the document's title element. The title element is the
     * first title element in the document element if the document element is
     * an svg element, othwerwise it is the first title element in the document.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#document.title
     *
     * @return Element|null
     */
    protected function getTitleElement()
    {
        $docElement = $this->getFirstElementChild();

        if ($docElement && $docElement instanceof SVGSVGElement) {
            // Find the first child of the document element that is a svg title
            // element.
            foreach ($docElement->mChildNodes as $child) {
                if ($child instanceof SVGTitleElement) {
                    return $child;
                }
            }
        } elseif ($docElement) {
            // Find the first title element in the document.
            $tw = new TreeWalker(
                $this,
                NodeFilter::SHOW_ELEMENT,
                function ($aNode) {
                    return $aNode instanceof HTMLTitleElement ?
                        NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
                }
            );

            return $tw->nextNode();
        }

        return null;
    }
}
