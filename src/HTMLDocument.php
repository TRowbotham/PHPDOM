<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLTitleElement;
use Rowbot\DOM\Element\SVG\SVGSVGElement;
use Rowbot\DOM\Element\SVG\SVGTitleElement;
use Rowbot\DOM\Exception\HierarchyRequestError;

use function assert;
use function preg_replace;
use function trim;

/**
 * HTMLDocument represents an HTML document.
 *
 * @property \Rowbot\DOM\Element\HTML\HTMLBodyElement $body Represents the HTML document's <body> element.
 * @property \Rowbot\DOM\Element\HTML\HTMLHeadElement $head Represents the HTML document's <head> element.
 * @property string                                   $title  Reflects the text content of the <title> element.
 */
class HTMLDocument extends Document
{
    public function __construct()
    {
        parent::__construct();

        $this->contentType = 'text/html';
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'body':
                return $this->getBodyElement();

            case 'head':
                return $this->getHeadElement();

            case 'title':
                return $this->getTitle();

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'body':
                $this->setBodyElement($value);

                break;

            case 'title':
                $this->setTitle((string) $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Gets the document's body element. The document's body element is the
     * first child of the html element that is either a body or frameset
     * element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#dom-document-body
     */
    protected function getBodyElement(): ?HTMLElement
    {
        $docElement = $this->getFirstElementChild();

        if ($docElement && $docElement instanceof HTMLHtmlElement) {
            // Get the first element in the document element that is a body or
            // frameset element.
            foreach ($docElement->childNodes as $child) {
                if ($child instanceof HTMLBodyElement || $child instanceof HTMLFrameSetElement) {
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
     */
    protected function getTitle(): string
    {
        $element = $this->getTitleElement();
        $value = '';

        if ($element) {
            // Concatenate the text data of all the text node children of the
            // title element.
            foreach ($element->childNodes as $child) {
                if ($child instanceof Text) {
                    $value .= $child->data;
                }
            }
        }

        // Trim whitespace and replace consecutive whitespace with a single
        // space.
        if ($value !== '') {
            $value = preg_replace('/[\t\n\f\r\x20]+/', ' ', trim($value, "\t\n\f\r\x20"));
            assert($value !== null);
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
     * @return \Rowbot\DOM\Element\HTML\HTMLTitleElement|\Rowbot\DOM\Element\SVG\SVGTitleElement|null
     */
    protected function getTitleElement(): ?Element
    {
        $docElement = $this->getFirstElementChild();

        if ($docElement && $docElement instanceof SVGSVGElement) {
            // Find the first child of the document element that is a svg title
            // element.
            foreach ($docElement->childNodes as $child) {
                if ($child instanceof SVGTitleElement) {
                    return $child;
                }
            }
        } elseif ($docElement) {
            // Find the first title element in the document.
            $node = $this->nextNode($this);

            while ($node) {
                if ($node instanceof HTMLTitleElement) {
                    return $node;
                }

                $node = $node->nextNode($this);
            }
        }

        return null;
    }

    /**
     * Sets the document's body element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#dom-document-body
     */
    protected function setBodyElement(HTMLElement $newBody): void
    {
        // The document's body can only be a body or frameset element. If the
        // new value being passed is not one of these, then throw an exception
        // and abort the algorithm.
        $isValidBody = $newBody instanceof HTMLBodyElement
            || $newBody instanceof HTMLFrameSetElement;

        if (!$isValidBody) {
            throw new HierarchyRequestError();
        }

        $oldBody = $this->getBodyElement();

        // Don't try setting the document's body to the same node.
        if ($newBody === $oldBody) {
            return;
        }

        // If there is a pre-existing body element, then replace it with the
        // new body element.
        if ($oldBody) {
            assert($oldBody->parentNode !== null);
            $oldBody->parentNode->replaceNode($newBody, $oldBody);

            return;
        }

        $docElement = $this->getFirstElementChild();

        // A body element can only exist as a child of the document element.
        // Throw an exception and abort the algorithm if the document element
        // does not exist.
        if (!$docElement) {
            throw new HierarchyRequestError();
        }

        $docElement->appendChild($newBody);
    }

    /**
     * Sets the text of the document's title element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#document.title
     */
    protected function setTitle(string $newTitle): void
    {
        $docElement = $this->getFirstElementChild();
        $element = null;

        if ($docElement && $docElement instanceof SVGSVGElement) {
            // Find the first child of the document element that is an
            // svg title element.
            foreach ($docElement->childNodes as $child) {
                if ($child instanceof SVGTitleElement) {
                    $element = $child;

                    break;
                }
            }

            // If there is no pre-existing svg title element, then create one
            // and insert it as the first child of the document element.
            if (!$element) {
                $element = ElementFactory::create(
                    $docElement->nodeDocument,
                    'title',
                    Namespaces::SVG
                );
                $docElement->insertNode($element, $docElement->childNodes->first());
            }

            $element->textContent = $newTitle;
        } elseif ($docElement && $docElement->namespaceURI === Namespaces::HTML) {
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
                    $docElement->nodeDocument,
                    'title',
                    Namespaces::HTML
                );
                $head->appendChild($element);
            }

            $element->textContent = $newTitle;
        }
    }
}
