<?php
require_once 'HTMLElement.class.php';

/**
 * Represents the HTML table caption element <caption>.
 *
 * @link https://html.spec.whatwg.org/#the-caption-element
 */
class HTMLTableCaptionElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
