<?php
require_once 'Document.class.php';
require_once 'EventListener.class.php';

class HTMLDocument extends Document {
	private $mHead;
	private $mTitle;
	private $mBody;

	public function __construct() {
		parent::__construct();

		$this->mContentType = 'text/html';
		$this->mDoctype = $this->implementation->createDocumentType('html', '', '');
		$this->mDocumentElement = $this->createElement('html');
		$this->mHead = $this->createElement('head');
		$this->mBody = $this->createElement('body');
		$this->mTitle = '';
		$this->mHead->appendChild($this->createElement('title'));
		$this->mDocumentElement->appendChild($this->mHead);
		$this->mDocumentElement->appendChild($this->mBody);
		$this->appendChild($this->mDoctype);
		$this->appendChild($this->mDocumentElement);
	}

	public function __get( $aName ) {
		switch ($aName) {
			case 'body':
				return $this->mBody;
			case 'head':
				return $this->mHead;
			case 'title':
				return $this->mTitle;
			default:
				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'title':
				$this->mTitle;

				break;
		}
	}

	public function toHTML() {
		$html = '';

		foreach($this->mChildNodes as $child) {
			$html .= $child->toHTML();
		}

		return $html;
	}

	public function __toString() {
		return $this->toHTML();
	}
}