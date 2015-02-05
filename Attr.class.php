<?php
require_once 'Node.class.php';

class Attr extends Node {
	protected $mNamespaceURI;
	protected $mPrefix;
	protected $mSpecified;

	public function __construct() {
		parent::__construct();

		$this->mNamespaceURI = null;
		$this->mNodeType = Node::ATTRIBUTE_NODE;
		$this->mPrefix = null;
		$this->mSpecified = false;
	}

	public function __get($aName) {
		switch ($aName) {
			case 'localName':
			case 'name':
				return $this->mNodeName;

			case 'namespaceURI':
				return $this->mNamespaceURI;

			case 'prefix':
				return $this->mPrefix;

			case 'specified':
				return $this->mSpecified;

			case 'value':
				return $this->mNodeValue;
		}
	}

	public static function _isBool($aAttributeName) {
		switch ($aAttributeName) {
			case 'async':
			case 'autofocus':
			case 'autoplay':
			case 'checked':
			case 'controls':
			case 'disabled':
			case 'default':
			case 'defer':
			case 'hidden':
			case 'ismap':
			case 'loop':
			case 'multiple':
			case 'novalidate':
			case 'ping':
			case 'readonly':
			case 'required':
			case 'reversed':
			case 'scoped':
				return true;

			default:
				return false;
		}
	}
}