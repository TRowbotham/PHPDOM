<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-ul-element

require_once 'HTMLElement.class.php';

class HTMLUListElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
