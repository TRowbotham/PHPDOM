<?php
namespace phpjs\elements\html;

/**
 * Represents the HTML picture element <picture>.
 *
 * @link https://html.spec.whatwg.org/#htmlpictureelement
 */
class HTMLPictureElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
