<?php
require_once 'Element.class.php';

class HTMLElement extends Element {

	protected $mAccessKey;
	protected $mAccessKeyLabel;
	protected $mContentEditable;
	protected $mDir;
	protected $mDataset;
	protected $mHidden;
	protected $mIsContentEditable;
	protected $mLang;
	protected $mSpellcheck;
	protected $mTabIndex;
	protected $mTitle;
	protected $mTranslate;

	protected function __construct() {
		parent::__construct();

		$this->mAccessKey = '';
		$this->mAccessKeyLabel = '';
		$this->mContentEditable = false;
		$this->mDataset;
		$this->mDir = '';
		$this->mHidden = false;
		$this->mIsContentEditable = false;
		$this->mLang = '';
		$this->mNodeType = Node::ELEMENT_NODE;
		$this->mSpellcheck = false;
		$this->mTabIndex = '';
		$this->mTitle = '';
		$this->mTranslate = false;
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
			case 'dir':
				return $this->mDir;
			case 'hidden':
				return $this->mHidden;
			case 'isContentEditable':
				return $this->mIsContentEditable;
			case 'lang':
				return $this->mLang;
			case 'spellcheck':
				return $this->mSpellcheck;
			case 'tabIndex':
				return $this->mTabIndex;
			case 'title':
				return $this->mTitle;
			case 'translate':
				return $this->mTranslate;
			default:
				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'accessKey':
				$this->mAccessKey = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'accessKeyLabel':
				$this->mAccessKeyLabel = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'contentEditable':
				$this->mContentEditable = $this->mIsContentEditable = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'dir':
				$this->mDir = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'hidden':
				$this->mHidden = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'lang':
				$this->mLang = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'spellcheck':
				$this->mSpellcheck = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'tabIndex':
				$this->mTabIndex = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'title':
				$this->mTitle = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'translate':
				$this->mTranslate = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			default:
				parent::__set($aName, $aValue);
		}
	}

	public function __toString() {
		return __CLASS__;
	}
}