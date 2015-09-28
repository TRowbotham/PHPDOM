<?php
require_once 'CharacterData.class.php';

/**
 * Represents the text content of a Node.
 *
 * @link https://dom.spec.whatwg.org/#text
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Text
 *
 * @property-read string $wholeText Returns the concatenated string data of all contingious Text nodes relative
 * 									to this Node in tree order.
 */
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