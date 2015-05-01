<?php
class DocumentFragment extends Node {
	public function __construct() {
		parent::__construct();

		$this->mNodeName = '#document-fragment';
		$this->mNodeType = Node::DOCUMENT_FRAGMENT_NODE;
	}
}