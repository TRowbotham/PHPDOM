<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-body-element

require_once 'HTMLElement.class.php';

class HTMLBodyElement extends HTMLElement {
	public function __construct($aTagName) {
		parent::__construct($aTagName);
	}
}
