<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Comment
// https://dom.spec.whatwg.org/#comment

require_once 'CharacterData.class.php';

class Comment extends CharacterData {
	public function __construct($aData = '') {
		parent::__construct();

		$this->mData = $aData;
		$this->mNodeName = '#comment';
		$this->mNodeType = Node::COMMENT_NODE;
	}

	public function toHTML() {
		return '<!-- ' . $this->mData . ' -->';
	}
}
