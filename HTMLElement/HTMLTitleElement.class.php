<?php
require_once( 'HTMLElement.class.php' );

class HTMLTitleElement extends HTMLElement {
	public $text;

	public function __construct() {
		parent::__construct();

		$this->mNodeName = 'TITLE';
		$this->mTagName = 'TITLE';
		$this->text = '';
	}

	public function __toString() {
		return __CLASS__;
	}
}