<?php
class HTMLSourceElement extends HTMLElement {
	public function __construct() {
		parent::__construct();

		$this->mEndTagOmitted = true;
		$this->mNodeName = 'SOURCE';
		$this->mTagName = 'SOURCE';
	}
}