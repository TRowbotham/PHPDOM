<?php
class HTMLSourceElement extends HTMLElement {
	public function __construct() {
		parent::__construct();

		$this->mNodeName = 'SOURCE';
		$this->mEndTagOmitted = true;
	}
}