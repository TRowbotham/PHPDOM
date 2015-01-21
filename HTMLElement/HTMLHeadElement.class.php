<?php
class HTMLHeadElement extends HTMLElement {

	public function __construct() {
		parent::__construct();

		$this->mNodeName = 'HEAD';
	}

	public function __toString() {
		return __CLASS__;
	}
}