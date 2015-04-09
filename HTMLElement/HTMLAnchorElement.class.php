<?php
require_once 'HTMLElement/HTMLElement.class.php';
require_once 'URLUtils.class.php';

class HTMLAnchorElement extends HTMLElement {
	use URLUtils;

	private $mDownload;
	private $mHrefLang;
	private $mPing;
	private $mRel;
	private $mRelList;
	private $mTarget;
	private $mType;

	public function __construct() {
		parent::__construct();

		$this->mDownload = '';
		$this->mHrefLang = '';
		$this->mNodeName = 'A';
		$this->mPing;
		$this->mRel = '';
		$this->mRelList = new DOMTokenList();
		$this->mTarget = '';
		$this->mType = '';
	}

	public function __get($aName) {
		$rv = $this->URLUtilsGet($aName);

		if ($rv !== false) {
			return $rv;
		}

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
				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		$this->URLUtilsSet($aName, $aValue);

		switch ($aName) {
			case 'download':
				$this->mDownload = $aValue;

				break;

			case 'hrefLang':
				$this->mHrefLang = $aValue;

				break;

			case 'ping':
				$this->mPing = $aValue;

				break;

			case 'rel':
				$this->mRel = $aValue;

				break;

			case 'target':
				$this->mTarget = $aValue;

				break;

			case 'type':
				$this->mType = $aValue;

				break;

			default:
				parent::__set($aName, $aValue);
		}
	}

	public function __toString() {
		return __CLASS__;
	}
}