<?php
// https://html.spec.whatwg.org/multipage/dom.html#htmlunknownelement

require_once 'HTMLElement.class.php';

class HTMLUnkownElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
