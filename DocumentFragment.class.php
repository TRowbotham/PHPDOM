<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DocumentFragment
// https://dom.spec.whatwg.org/#interface-documentfragment

require_once 'ParentNode.class.php';

class DocumentFragment extends Node {
	use ParentNode;

	public function __construct() {
		parent::__construct();

		$this->mNodeName = '#document-fragment';
		$this->mNodeType = Node::DOCUMENT_FRAGMENT_NODE;
	}
}