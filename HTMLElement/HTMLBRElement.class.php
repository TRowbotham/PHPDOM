<?php
namespace phpjs;

// https://html.spec.whatwg.org/multipage/semantics.html#the-br-element

require_once 'HTMLElement.class.php';

class HTMLBRElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mEndTagOmitted = true;
    }
}
