<?php
namespace phpjs;

// https://html.spec.whatwg.org/multipage/semantics.html#the-dl-element

require_once 'HTMLElement.class.php';

class HTMLDListElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
