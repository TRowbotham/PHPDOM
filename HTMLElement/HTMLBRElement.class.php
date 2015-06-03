<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-br-element

require_once 'HTMLElement.class.php';

class HTMLBRElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
