<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\{
    Listable,
    Resettable,
    Submittable
};

/**
 * @see https://html.spec.whatwg.org/multipage/forms.html#the-select-element
 */
class HTMLSelectElement extends HTMLElement implements
    Listable,
    Resettable,
    Submittable
{
    protected function __construct()
    {
        parent::__construct();
    }
}
