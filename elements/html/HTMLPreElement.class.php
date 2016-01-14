<?php
namespace phpjs\elements\html;

// https://html.spec.whatwg.org/multipage/semantics.html#the-pre-element
class HTMLPreElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
