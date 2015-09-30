<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Attr
// https://dom.spec.whatwg.org/#attr

class Attr {
	protected $mLocalName;
	protected $mName;
	protected $mNamespaceURI;
	protected $mOwnerElement;
	protected $mPrefix;
	protected $mValue;

	public function __construct(Element $aOwnerElement, $aLocalName, $aValue, $aNamespace = null, $aPrefix = null) {
		$this->mLocalName = $aLocalName;
		$this->mName = $aPrefix ? $aPrefix . ':' . $aLocalName : $aLocalName;
		$this->mNamespaceURI = null;
		$this->mOwnerElement = $aOwnerElement;
		$this->mPrefix = $aPrefix;
		$this->mValue = $aValue;
	}

	public function __get($aName) {
		switch ($aName) {
			case 'localName':
				return $this->mLocalName;

			case 'name':
				return $this->mName;

			case 'namespaceURI':
				return $this->mNamespaceURI;

			case 'ownerElement':
				return $this->mOwnerElement;

			case 'prefix':
				return $this->mPrefix;

			case 'value':
				return $this->mValue;
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
