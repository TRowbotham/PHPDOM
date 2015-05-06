<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Attr
// https://dom.spec.whatwg.org/#attr

class Attr {
	protected $mLocalName;
	protected $mName;
	protected $mNamespaceURI;
	protected $mPrefix;
	protected $mValue;

	public function __construct($aName) {
		parent::__construct();

		$this->mLocalName = $aName;
		$this->mName = $aName;
		$this->mNamespaceURI = null;
		$this->mPrefix = null;
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

			case 'value':
				return $this->mValue;

			default:
				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'value':
				$this->mValue = $aValue;
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