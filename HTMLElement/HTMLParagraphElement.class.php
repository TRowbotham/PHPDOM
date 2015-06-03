<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-p-element

require_once 'HTMLElement.class.php';

class HTMLParagraphElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
