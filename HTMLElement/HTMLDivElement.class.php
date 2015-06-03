<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-div-element

require_once 'HTMLElement.class.php';

class HTMLDivElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
