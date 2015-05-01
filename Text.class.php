<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Text
// https://dom.spec.whatwg.org/#text

require_once 'CharacterData.class.php';

class Text extends CharacterData {
	private $this->mWholeText;

	public function __construct($aData = '') {
		$this->nodeName = '#text';
		$this->nodeType = Node::TEXT_NODE;
		$this->mWholeText = $aData;
	}

	public function __get($aName) {
		switch ($aName) {
			case 'wholeText':
				return $this->mWholeText;
			default:
				return parent::__get($aName);
		}
	}

	public function splitText($aOffset) {
		// TODO
	}
}