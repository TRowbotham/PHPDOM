<?php
namespace phpjs;

// https://html.spec.whatwg.org/multipage/semantics.html#the-hr-element

require_once 'HTMLElement.class.php';

class HTMLHRElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mEndTagOmitted = true;
    }
}
