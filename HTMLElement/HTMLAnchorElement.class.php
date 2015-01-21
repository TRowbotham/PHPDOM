<?php
require_once 'HTMLElement/HTMLElement.class.php';

class HTMLAnchorElement extends HTMLElement {

	private $mDownload;
	private $mHref;
	private $mHrefLang;
	private $mPing;
	private $mRel;
	private $mRelList;
	private $mTarget;
	private $mType;

	public function __construct() {
		parent::__construct();

		$this->mDownload = '';
		$this->mHref = '';
		$this->mHrefLang = '';
		$this->mNodeName = 'A';
		$this->mPing;
		$this->mRel = '';
		$this->mRelList = new DOMTokenList();
		$this->mTarget = '';
		$this->mType = '';
	}

	public function __get($aName) {
		switch ($aName) {
			case 'download':
				return $this->mDownload;
			case 'href':
				return $this->mHref;
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

	public function __toString() {
		return __CLASS__;
	}
}