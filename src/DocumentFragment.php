<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\TreeWalker;

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

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->host = null;
        $this->nodeType = Node::DOCUMENT_FRAGMENT_NODE;
    }

    /**
     * {@inheritDoc}
     */
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
     * @return \Rowbot\DOM\Element\Element|null
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
     *
     * @return void
     */
    public function setHost(?Element $host): void
    {
        $this->host = $host;
    }

    /**
     * {@inheritDoc}
     */
    public function getLength(): int
    {
        return count($this->childNodes);
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeName(): string
    {
        return '#document-fragment';
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeValue(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    protected function setNodeValue(?string $value): void
    {
        // Do nothing.
    }

    /**
     * {@inheritDoc}
     */
    protected function setTextContent(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $node = null;

        if ($value !== '') {
            $node = new Text($value);
            $node->nodeDocument = $this->nodeDocument;
        }

        $this->replaceAllNodes($node);
    }
}
