<?php
// https://html.spec.whatwg.org/multipage/dom.html#htmlunknownelement

require_once 'HTMLElement.class.php';

class HTMLUnknownElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
