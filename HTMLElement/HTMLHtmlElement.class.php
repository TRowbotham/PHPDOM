<?php
namespace phpjs;

// https://html.spec.whatwg.org/multipage/semantics.html#the-html-element

require_once 'HTMLElement.class.php';

class HTMLHtmlElement extends HTMLElement {
	public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
		parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
	}
}
