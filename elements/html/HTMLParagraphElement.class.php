<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-p-element
 */
class HTMLParagraphElement extends HTMLElement
{
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
