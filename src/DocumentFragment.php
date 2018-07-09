<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\TreeWalker;

/**
 * @see https://dom.spec.whatwg.org/#interface-documentfragment
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DocumentFragment
 */
class DocumentFragment extends Node
{
    use NonElementParentNode;
    use ParentNode;

    protected $host;

    public function __construct()
    {
        parent::__construct();

        $this->host = null;
        $this->nodeType = Node::DOCUMENT_FRAGMENT_NODE;
    }

    public function __get($name)
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
     * {@inheritDoc}
     */
    public function cloneNodeInternal(
        Document $document = null,
        bool $cloneChildren = false
    ): Node {
        $document = $document ?: $this->getNodeDocument();
        $copy = new static();
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    /**
     * Gets a DocumentFragment's host object.
     *
     * @internal
     *
     * @return Element|null
     */
    public function getHost()
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
     * @param Element|null $host The element that is hosting the
     *     DocumentFragment such as a template element or shadow root.
     */
    public function setHost(Element $host = null)
    {
        $this->host = $host;
    }

    /**
     * Returns the Node's length, which is the number of child nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-length
     * @see Node::getLength()
     *
     * @return int
     */
    public function getLength(): int
    {
        return \count($this->childNodes);
    }

    /**
     * @see Node::getNodeName
     */
    protected function getNodeName(): string
    {
        return '#document-fragment';
    }

    /**
     * @see Node::getNodeValue
     */
    protected function getNodeValue(): ?string
    {
        return null;
    }

    /**
     * @see Node::getTextContent
     */
    protected function getTextContent(): string
    {
        $tw = new TreeWalker($this, NodeFilter::SHOW_TEXT);
        $data = '';

        while (($node = $tw->nextNode())) {
            $data .= $node->data;
        }

        return $data;
    }

    /**
     * @see Node::setNodeValue
     */
    protected function setNodeValue($newValue): void
    {
        // Do nothing.
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($newValue): void
    {
        $node = null;
        $newValue = Utils::DOMString($newValue, true);

        if ($newValue !== '') {
            $node = new Text($newValue);
            $node->nodeDocument = $this->nodeDocument;
        }

        $this->replaceAllNodes($node);
    }
}
