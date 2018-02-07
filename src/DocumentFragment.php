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
    public function getLength()
    {
        return \count($this->childNodes);
    }

    /**
     * @see Node::getNodeName
     */
    protected function getNodeName()
    {
        return '#document-fragment';
    }

    /**
     * @see Node::getNodeValue
     */
    protected function getNodeValue()
    {
        return null;
    }

    /**
     * @see Node::getTextContent
     */
    protected function getTextContent()
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
    protected function setNodeValue($newValue)
    {
        // Do nothing.
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($newValue)
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
