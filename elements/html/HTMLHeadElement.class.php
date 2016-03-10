<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-head-element
 */
class HTMLHeadElement extends HTMLElement
{
	public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
		parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
	}
}
