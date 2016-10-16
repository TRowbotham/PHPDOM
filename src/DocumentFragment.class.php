<?php
namespace phpjs;

use phpjs\elements\Element;
use phpjs\NodeFilter;
use phpjs\TreeWalker;

/**
 * @see https://dom.spec.whatwg.org/#interface-documentfragment
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DocumentFragment
 */
class DocumentFragment extends Node
{
    use NonElementParentNode;
    use ParentNode;

    protected $mHost;

    public function __construct()
    {
        parent::__construct();

        $this->mHost = null;
        $this->mNodeType = Node::DOCUMENT_FRAGMENT_NODE;
    }

    public function __destruct()
    {
        $this->mHost = null;
        parent::__destruct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'childElementCount':
                return $this->getChildElementCount();

            case 'children':
                return $this->getChildren();

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'lastElementChild':
                return $this->getLastElementChild();

            default:
                return parent::__get($aName);
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
        return $this->mHost;
    }

    /**
     * Sets a DocumentFragment's host element, if it has one.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-documentfragment-host
     *
     * @param Element|null $aHost The element that is hosting the
     *     DocumentFragment such as a template element or shadow root.
     */
    public function setHost(Element $aHost = null)
    {
        $this->mHost = $aHost;
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
        return count($this->mChildNodes);
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
    protected function setNodeValue($aNewValue)
    {
        // Do nothing.
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($aNewValue)
    {
        $node = null;
        $newValue = Utils::DOMString($aNewValue, true);

        if ($newValue !== '') {
            $new = new Text($newValue);
            $node->mOwnerDocument = $this->mOwnerDocument;
        }

        $this->_replaceAll($node);
    }
}
