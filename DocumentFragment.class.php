<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DocumentFragment
// https://dom.spec.whatwg.org/#interface-documentfragment

class DocumentFragment extends Node {
	public function __construct() {
		parent::__construct();

		$this->mNodeName = '#document-fragment';
		$this->mNodeType = Node::DOCUMENT_FRAGMENT_NODE;
	}
}