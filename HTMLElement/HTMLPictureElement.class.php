<?php
require_once 'HTMLElement.class.php';

/**
 * Represents the HTML picture element <picture>.
 *
 * @link https://html.spec.whatwg.org/#htmlpictureelement
 */
class HTMLPictureElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
