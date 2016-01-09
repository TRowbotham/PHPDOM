<?php
namespace phpjs;

// https://html.spec.whatwg.org/multipage/semantics.html#the-h1,-h2,-h3,-h4,-h5,-and-h6-elements

require_once 'HTMLElement.class.php';

class HTMLHeadingElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
