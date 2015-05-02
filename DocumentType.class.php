<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DocumentType
// https://dom.spec.whatwg.org/#documenttype

require_once 'Node.class.php';
require_once 'ChildNode.class.php';

class DocumentType extends Node {
	use ChildNode;

	private $mName;
	private $mPublicId;
	private $mSystemId;

	public function __construct($aName, $aPublicId = '', $aSystemId = '') {
		parent::__construct();

		$this->mName = $aName;
		$this->mNodeName = $aName;
		$this->mNodeType = Node::DOCUMENT_TYPE_NODE;
		$this->mPublicId = $aPublicId;
		$this->mSystemId = $aSystemId;
	}

	public function __get($aName) {
		switch ($aName) {
			case 'name':
				return $this->mName;
			case 'publicId':
				return $this->mPublicId;
			case 'systemId':
				return $this->mSystemId;
			default:
				return parent::__get($aName);
		}
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