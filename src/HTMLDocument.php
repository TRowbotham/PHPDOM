<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Exception\HierarchyRequestError;

use function assert;

/**
 * HTMLDocument represents an HTML document.
 *
 * @property \Rowbot\DOM\Element\HTML\HTMLBodyElement $body Represents the HTML document's <body> element.
 * @property \Rowbot\DOM\Element\HTML\HTMLHeadElement $head Represents the HTML document's <head> element.
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
}
