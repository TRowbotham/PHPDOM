<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-head-element

require_once 'HTMLElement.class.php';

class HTMLHeadElement extends HTMLElement {
	public function __construct($aTagName) {
		parent::__construct($aTagName);
	}
}
