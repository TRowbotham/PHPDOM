<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-h1,-h2,-h3,-h4,-h5,-and-h6-elements
 */
class HTMLHeadingElement extends HTMLElement
{
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }
}
