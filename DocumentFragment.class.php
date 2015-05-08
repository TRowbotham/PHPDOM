<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DocumentFragment
// https://dom.spec.whatwg.org/#interface-documentfragment

require_once 'ParentNode.class.php';
require_once 'NonElementParentNode.class.php';

class DocumentFragment extends Node {
	use ParentNode, NonElementParentNode;

	public function __construct() {
		parent::__construct();

		$this->mNodeName = '#document-fragment';
		$this->mNodeType = Node::DOCUMENT_FRAGMENT_NODE;
	}

    public function __get($aName) {
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
}
