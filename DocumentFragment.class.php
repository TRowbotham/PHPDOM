<?php
namespace phpjs;

use phpjs\elements\Element;

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
        $this->mNodeName = '#document-fragment';
        $this->mNodeType = Node::DOCUMENT_FRAGMENT_NODE;
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
}
