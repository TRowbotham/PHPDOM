<?php
require_once 'HTMLElement.class.php';

/**
 * Represents the HTML table caption element <caption>.
 *
 * @link https://html.spec.whatwg.org/#the-caption-element
 */
class HTMLTableCaptionElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
