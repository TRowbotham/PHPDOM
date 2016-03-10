<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#htmlunknownelement
 */
class HTMLUnknownElement extends HTMLElement
{
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
