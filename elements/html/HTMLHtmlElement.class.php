<?php
namespace phpjs\elements\html;

// https://html.spec.whatwg.org/multipage/semantics.html#the-html-element
class HTMLHtmlElement extends HTMLElement {
	public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
		parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
	}
}
