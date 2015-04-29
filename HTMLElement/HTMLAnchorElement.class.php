<?php
require_once 'HTMLElement/HTMLElement.class.php';
require_once 'URLUtils.class.php';

class HTMLAnchorElement extends HTMLElement {
	private $mDownload;
	private $mHrefLang;
	private $mPing;
	private $mRel;
	private $mRelList;
	private $mTarget;
	private $mType;
	private $mURLUtils;

	public function __construct() {
		parent::__construct();

		$this->mDownload = '';
		$this->mHrefLang = '';
		$this->mNodeName = 'A';
		$this->mPing;
		$this->mRel = '';
		$this->mRelList = new DOMTokenList();
		$this->mTagName = 'A';
		$this->mTarget = '';
		$this->mType = '';
		$this->mURLUtils = new URLUtils();

		$this->mURLUtils->attach($this);
	}

	public function __get($aName) {
		switch ($aName) {
			case 'download':
				return $this->mDownload;
			case 'hrefLang':
				return $this->mHrefLang;
			case 'ping':
				return $this->mPing;
			case 'rel':
				return $this->mRel;
			case 'relList':
				return $this->mRelList;
			case 'target':
				return $this->mTarget;
			case 'type':
				return $this->mType;
			default:
				$rv = $this->mURLUtils->__get($aName);

				if ($rv !== false) {
					return $rv;
				}

				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'download':
				$this->mDownload = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'hrefLang':
				$this->mHrefLang = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'ping':
				$this->mPing = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'rel':
				$this->mRel = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'target':
				$this->mTarget = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'type':
				$this->mType = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			default:
				$this->mURLUtils->__set($aName, $aValue);
				parent::__set($aName, $aValue);
		}
	}

	public function update(SplSubject $aObject) {
		if ($aObject instanceof URLUtils) {
			$this->_updateAttributeOnPropertyChange('href', $this->mURLUtils->href);
		} else if ($aObject instanceof DOMTokenList && $aObject == $this->mRelList) {
			$this->mRel = $this->mRelList->toString();
			$this->_updateAttributeOnPropertyChange('rel', $this->mRel);
		}

		parent::update($aObject);
	}

	public function __toString() {
		return __CLASS__;
	}
}