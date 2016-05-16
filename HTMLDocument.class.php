<?php
namespace phpjs;

use phpjs\elements\ElementFactory;
use phpjs\elements\html\HTMLBodyElement;
use phpjs\elements\html\HTMLFrameSetElement;
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
                $this->setBodyElement($aValue);

                break;

            case 'title':
                $this->setTitle($aValue);

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
                ['', '', ' '],
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

    /**
     * Sets the document's body element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#dom-document-body
     *
     * @param HTMLElement $aNewBody The new body element.
     */
    protected function setBodyElement($aNewBody)
    {
        // The document's body can only be a body or frameset element. If the
        // new value being passed is not one of these, then throw an exception
        // and abort the algorithm.
        $isValidBody = $aNewBody instanceof HTMLBodyElement ||
            $aNewBody instanceof HTMLFrameSetElement;

        if (!$isValidBody) {
            throw new HierarchyRequestError();
            return;
        }

        $oldBody = $this->getBodyElement();

        // Don't try setting the document's body to the same node.
        if ($aNewBody === $oldBody) {
            return;
        }

        // If there is a pre-existing body element, then replace it with the
        // new body element.
        if ($oldBody) {
            $oldBody->mParentNode->replaceNode($aNewBody, $oldBody);
            return;
        }

        $docElement = $this->getFirstElementChild();

        // A body element can only exist as a child of the document element.
        // Throw an exception and abort the algorithm if the document element
        // does not exist.
        if (!$docElement) {
            throw new HierarchyRequestError();
            return;
        }

        if (!$oldBody) {
            $docElement->appendChild($aNewBody);
        }
    }

    /**
     * Sets the text of the document's title element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#document.title
     *
     * @param string $aNewTitle The new title.
     */
    protected function setTitle($aNewTitle)
    {
        $docElement = $this->getFirstElementChild();
        $element = null;

        if ($docElement && $docElement instanceof SVGSVGElement) {
            // Find the first child of the document element that is an
            // svg title element.
            foreach ($docElement->mChildNodes as $child) {
                if ($child instanceof SVGTitleElement) {
                    $element = $child;
                    break;
                }
            }

            // If there is no pre-existing svg title element, then create one
            // and insert it as the first child of the document element.
            if (!$element) {
                $element = ElementFactory::create(
                    $docElement->mOwnerDocument,
                    'title',
                    Namespaces::SVG
                );
                $docElement->insertNode($element, $docElement->mFirstChild);
            }

            $element->textContent = $aNewTitle;
        } elseif (
            $docElement &&
            $docElement->namespaceURI === Namespaces::HTML
        ) {
            $element = $this->getTitleElement();
            $head = $this->getHeadElement();

            // The title element can only exist in the head element. If neither
            // of these exist, then there is no title element to set and no
            // place to insert a new one.
            if (!$element && !$head) {
                return;
            }

            // If there is no pre-existing title element, then create one
            // and append it to the head element.
            if (!$element) {
                $element = ElementFactory::create(
                    $docElement->mOwnerDocument,
                    'title',
                    Namespaces::HTML
                );
                $head->appendChild($element);
            }

            $element->textContent = $aNewTitle;
        }
    }
}
