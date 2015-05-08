<?php
// https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
// https://dom.spec.whatwg.org/#characterdata

require_once 'Node.class.php';
require_once 'ChildNode.class.php';

abstract class CharacterData extends Node {
	use ChildNode;

	private $mData;
	private $mLength;

	public function __construct() {
		parent::__construct();

		$this->mData = '';
		$this->mLength = 0;
	}

	public function __get($aName) {
		switch ($aName) {
			case 'data':
				return $this->mData;
			case 'length':
				return $this->mLength;
			default:
				return parent::__get($aName);
		}
	}

	public function appendData($aData) {
		// TODO
	}

	public function deleteData($aOffset, $aCount) {
		// TODO
	}

	public function insertData($aOffset, $aData) {
		// TODO
	}

	public function replaceData($aOffset, $aCount, $aData) {
		// TODO
	}

	public function substringData($aOffset, $aCount) {
		// TODO
	}
}
