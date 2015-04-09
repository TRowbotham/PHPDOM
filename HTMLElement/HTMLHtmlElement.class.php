<?php
require_once( 'HTMLElement/HTMLElement.class.php' );

class HTMLHtmlElement extends HTMLElement {

	public function __construct() {
		parent::__construct();

		$this->mNodeName = 'HTML';
		$this->mTagName = 'HTML';
	}

	public function __toString() {
		return __CLASS__;
	}
}