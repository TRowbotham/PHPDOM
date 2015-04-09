<?php
require_once( 'HTMLElement/HTMLBodyElement.class.php' );

class HTMLBodyElement extends HTMLElement {

	public function __construct() {
		parent::__construct();

		$this->mNodeName = 'BODY';
		$this->mTagName = 'BODY';
	}

	public function __toString() {
		return __CLASS__;
	}
}