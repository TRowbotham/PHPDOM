<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Text
// https://dom.spec.whatwg.org/#text

require_once 'CharacterData.class.php';

class Text extends CharacterData {
	public function __construct($aData = '') {
		parent::__construct();

		$this->mNodeName = '#text';
		$this->mNodeType = Node::TEXT_NODE;
		$this->mData = $aData;
		$this->mLength = strlen($aData);
	}

	public function __get($aName) {
		switch ($aName) {
			case 'wholeText':
				$wholeText = '';

				if ($this->parentNode) {
					foreach ($this->parentNode->childNodes as $node) {
						if ($node instanceof Text) {
							$wholeText += $node->data;
						}
					}
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