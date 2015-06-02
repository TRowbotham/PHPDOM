<?php
require_once( 'HTMLElement.class.php' );

class HTMLTitleElement extends HTMLElement {
	private $mText;

	public function __construct() {
		parent::__construct();

		$this->mNodeName = 'TITLE';
		$this->mTagName = 'TITLE';
		$this->mText = '';
	}

	public function __get($aName) {
		switch ($aName) {
			case 'text':
				return $this->mText;

			default:
				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'text':
			case 'textContent':
				if (!is_string($aValue)) {
					break;
				}

				$this->mText = $this->mTextContent = $aValue;

				foreach ($this->mChildNodes as $child) {
					// It should be safe to assume that any node we encounter
					// here uses the ChildNode trait.
					$child->remove();
				}

				$this->append($aValue);

				break;

			default:
				parent::__set($aName, $aValue);
		}
	}

	public function __toString() {
		return __CLASS__;
	}
}
