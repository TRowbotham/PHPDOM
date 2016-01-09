<?php
namespace phpjs;

// https://html.spec.whatwg.org/multipage/dom.html#htmlunknownelement

require_once 'HTMLElement.class.php';

class HTMLUnknownElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
