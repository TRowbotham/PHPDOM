<?php
require_once 'Document.class.php';
require_once 'EventListener.class.php';

class HTMLDocument extends Document implements EventListener {
	private $mDocumentElement;
	private $mHead;
	private $mTitle;
	private $mBody;

	public function __construct($aTitle = null) {
		parent::__construct();

		$this->mDocumentElement = $this->createElement('html');
		$this->mHead = $this->createElement('head');
		$this->mBody = $this->createElement('body');

		$title = $this->createElement('title');
		$title->textContent = $aTitle ? $aTitle : '';

		$this->mHead->appendChild($title);
		$this->documentElement->appendChild($this->mHead);
		$this->documentElement->appendChild($this->mBody);
		$this->appendChild($this->mDoctype);
		$this->appendChild($this->mDocumentElement);
	}

	public function __get( $aName ) {
		switch ($aName) {
			case 'body':
				return $this->mBody;
			case 'documentElement':
				return $this->mDocumentElement;
			case 'head':
				return $this->mHead;
			case 'title':
				return $this->mTitle;
			default:
				return parent::__get($aName);
		}
	}

	public function handleEvent(Event $aEvent) {
		switch ($aEvent->type) {
			case 'doctypeChange':
				$this->removeChild($this->mDoctype);
				$this->mDoctype =& $aEvent->detail;
				$this->insertBefore($this->mDoctype, $this->mFirstChild);

				$this->removeEventListener('doctypeChange', $this);
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