<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-pre-element

require_once 'HTMLElement.class.php';

class HTMLPreElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
