<?php
namespace phpjs\elements\html;

// https://html.spec.whatwg.org/multipage/semantics.html#the-body-element
class HTMLBodyElement extends HTMLElement {
	public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
		parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
	}
}
