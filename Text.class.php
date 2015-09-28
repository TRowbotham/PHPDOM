<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Text
// https://dom.spec.whatwg.org/#text

require_once 'CharacterData.class.php';

class Text extends CharacterData {
	public function __construct($aData = '') {
		parent::__construct();

		$this->mData = $aData;
		$this->mNodeName = '#text';
		$this->mNodeType = Node::TEXT_NODE;
	}

	public function __get($aName) {
		switch ($aName) {
			case 'wholeText':
				$wholeText = '';
				$startNode = $this;

				while ($startNode) {
					if (!($startNode->previousSibling instanceof Text)) {
						break;
					}

					$startNode = $startNode->previousSibling;
				}

				while ($startNode instanceof Text) {
					$wholeText .= $startNode->data;
					$startNode = $startNode->nextSibling;
				}

				return $wholeText;

			default:
				return parent::__get($aName);
		}
	}

	public function splitText($aOffset) {
		// TODO
	}
}