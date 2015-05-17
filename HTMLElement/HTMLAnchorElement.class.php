<?php
require_once 'HTMLElement/HTMLElement.class.php';
require_once 'URLUtils.class.php';

class HTMLAnchorElement extends HTMLElement {
	use URLUtils;

	private $mDownload;
	private $mHrefLang;
	private $mInvalidateRelList;
	private $mPing;
	private $mRel;
	private $mRelList;
	private $mTarget;
	private $mType;

	public function __construct() {
		parent::__construct();
		$this->initURLUtils();

		$this->mDownload = '';
		$this->mHrefLang = '';
		$this->mInvalidateRelList = false;
		$this->mNodeName = 'A';
		$this->mPing;
		$this->mRel = '';
		$this->mRelList = null;
		$this->mTagName = 'A';
		$this->mTarget = '';
		$this->mType = '';
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
				return $this->getRelList();
			case 'target':
				return $this->mTarget;
			case 'type':
				return $this->mType;
			default:
				$rv = $this->URLUtilsGetter($aName);

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
				$this->mInvalidateRelList = true;
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
				$this->URLUtilsSetter($aName, $aValue);
				parent::__set($aName, $aValue);
		}
	}

	public function update(SplSubject $aObject) {
		if ($aObject instanceof URLSearchParams) {
			$this->mUrl->mQuery = $aObject->toString();
			$this->preupdate();
		} elseif ($aObject instanceof DOMTokenList && $aObject == $this->mRelList) {
			$this->mRel = $this->getRelList()->__toString();
			$this->_updateAttributeOnPropertyChange('rel', $this->mRel);
		}

		parent::update($aObject);
	}

	public function __toString() {
		return __CLASS__;
	}

	private function getBaseURL() {
		return URLParser::basicURLParser($this->mOwnerDocument->baseURI);
	}

	private function getRelList() {
		if (!$this->mRelList || $this->mInvalidateRelList) {
			$this->mInvalidateRelList = false;
			$this->mRelList = new DOMTokenList();
			$this->mRelList->attach($this);

			if (!empty($this->mRel)) {
				$this->mRelList->add($this->mRel);
			}
		}

		return $this->mRelList;
	}

	private function updateURL($aValue) {
		$this->_updateAttributeOnPropertyChange('href', $aValue);
	}
}
