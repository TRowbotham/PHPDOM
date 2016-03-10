<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-br-element
 */
class HTMLBRElement extends HTMLElement
{
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mEndTagOmitted = true;
    }
}
