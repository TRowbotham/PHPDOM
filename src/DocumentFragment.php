<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

use function count;

/**
 * @see https://dom.spec.whatwg.org/#interface-documentfragment
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DocumentFragment
 */
class DocumentFragment extends Node implements NonElementParentNode, ParentNode
{
    use NonElementParentNodeTrait;
    use ParentNodeTrait;

    /**
     * @var \Rowbot\DOM\Element\Element|null
     */
    protected $host;

    public function __construct(Document $document)
    {
        parent::__construct($document);

        $this->host = null;
        $this->nodeType = Node::DOCUMENT_FRAGMENT_NODE;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'childElementCount':
                return $this->getChildElementCount();

            case 'children':
                return $this->getChildren();

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'lastElementChild':
                return $this->getLastElementChild();

            default:
                return parent::__get($name);
        }
    }

    /**
     * @return static
     */
    public function cloneNodeInternal(Document $document = null, bool $cloneChildren = false): Node
    {
        $document = $document ?? $this->getNodeDocument();
        $copy = new static($document);
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    /**
     * Gets a DocumentFragment's host object.
     *
     * @internal
     */
    public function getHost(): ?Element
    {
        return $this->host;
    }

    /**
     * Sets a DocumentFragment's host element, if it has one.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-documentfragment-host
     *
     * @param \Rowbot\DOM\Element\Element|null $host The element that is hosting the DocumentFragment such as a template
     *                                         element or shadow root.
     */
    public function setHost(?Element $host): void
    {
        $this->host = $host;
    }

    public function getLength(): int
    {
        return count($this->childNodes);
    }

    protected function getNodeName(): string
    {
        return '#document-fragment';
    }

    protected function getNodeValue(): ?string
    {
        return null;
    }

    protected function getTextContent(): string
    {
        $tw = new TreeWalker($this, NodeFilter::SHOW_TEXT);
        $data = '';

        while (($node = $tw->nextNode())) {
            $data .= $node->data;
        }

        return $data;
    }

    protected function setNodeValue(?string $value): void
    {
        // Do nothing.
    }

    protected function setTextContent(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $node = null;

        if ($value !== '') {
            $node = new Text($this->nodeDocument, $value);
        }

        $this->replaceAllNodes($node);
    }
}
