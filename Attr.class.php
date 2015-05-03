<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Attr
// https://dom.spec.whatwg.org/#attr

require_once 'Node.class.php';

class Attr extends Node {
	protected $mLocalName;
	protected $mName;
	protected $mNamespaceURI;
	protected $mPrefix;
	protected $mSpecified;
	protected $mValue;

	public function __construct($aName) {
		parent::__construct();

		$this->mLocalName = $aName;
		$this->mName = $aName;
		$this->mNodeName = $aName;
		$this->mNamespaceURI = null;
		$this->mNodeType = Node::ATTRIBUTE_NODE;
		$this->mPrefix = null;
		$this->mSpecified = true;
		$this->mValue = '';
	}

	public function __get($aName) {
		switch ($aName) {
			case 'localName':
				return $this->mLocalName;

			case 'name':
				return $this->mName;

			case 'namespaceURI':
				return $this->mNamespaceURI;

			case 'prefix':
				return $this->mPrefix;

			case 'specified':
				return $this->mSpecified;

			case 'value':
				return $this->mValue;

			default:
				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'nodeValue':
			case 'textContent':
			case 'value':
				$this->mNodeValue = $aValue;
				$this->mTextContent = $aValue;
				$this->mValue = $aValue;

				break;

			default:
				parent::__get($aName);
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