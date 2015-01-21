<?php
require_once 'Element.class.php';

class HTMLElement extends Element {

	protected $mAccessKey;
	protected $mAccessKeyLabel;
	protected $mContentEditable;
	protected $mDataset;
	protected $mIsContentEditable;
	protected $mLang;
	protected $mTabIndex;
	protected $mTitle;

	protected function __construct() {
		parent::__construct();

		$this->mAccessKey = '';
		$this->mAccessKeyLabel = '';
		$this->mContentEditable = false;
		$this->mDataset;
		$this->mIsContentEditable = false;
		$this->mLang = '';
		$this->mNodeType = Node::ELEMENT_NODE;
		$this->mTabIndex = '';
		$this->mTitle = '';
	}

	public function __get($aName) {
		switch ($aName) {
			case 'accessKey':
				return $this->mAccessKey;
			case 'accessKeyLabel':
				return $this->mAccessKeyLabel;
			case 'contentEditable':
				return $this->mContentEditable;
			case 'dataset':
				return $this->mDataset;
			case 'isContentEditable':
				return $this->mIsContentEditable;
			case 'lang':
				return $this->mLang;
			case 'tabIndex':
				return $this->mTabIndex;
			case 'title':
				return $this->mTitle;
			default:
				return parent::__get($aName);
		}
	}

	public function __toString() {
		return __CLASS__;
	}
}