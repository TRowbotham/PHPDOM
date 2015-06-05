<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-hr-element

require_once 'HTMLElement.class.php';

class HTMLHRElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);

        $this->mEndTagOmitted = true;
    }
}
