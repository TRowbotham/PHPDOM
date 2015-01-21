<?php
require_once 'Node.class.php';

class DocumentType extends Node {
	private $mName;
	private $mPublicId;
	private $mSystemId;

	public function __construct($aName = '', $aPublicId = '', $aSystemId = '') {
		parent::__construct();

		$this->mName = $aName;
		$this->mNodeType = Node::DOCUMENT_TYPE_NODE;
		$this->mPublicId = $aPublicId;
		$this->mSystemId = $aSystemId;
	}

	public function toHTML() {
		$html = '<!DOCTYPE';
		$html .= ($this->mName ? ' ' . $this->mName  : '');
		$html .= ($this->mPublicId ? ' ' . $this->mPublicId : '');
		$html .= ($this->mSystemId ? ' ' . $this->mSystemId : '');
		$html .= '>';

		return $html;
	}
}