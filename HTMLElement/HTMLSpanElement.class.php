<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-span-element

require_once 'HTMLElement.class.php';

class HTMLSpanElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
