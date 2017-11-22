<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\Listable;

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-object-element
 */
class HTMLObjectElement extends HTMLElement implements Listable
{
    protected function __construct()
    {
        parent::__construct();
    }
}
