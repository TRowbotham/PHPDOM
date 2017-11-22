<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\{
    Listable,
    Submittable
};

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-object-element
 */
class HTMLObjectElement extends HTMLElement implements Listable, Submittable
{
    protected function __construct()
    {
        parent::__construct();
    }
}
