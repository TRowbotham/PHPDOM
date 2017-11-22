<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\{
    Listable,
    Submittable
};

/**
 * @see https://html.spec.whatwg.org/multipage/forms.html#the-button-element
 */
class HTMLButtonElement extends HTMLElement implements Listable, Submittable
{
    protected function __construct()
    {
        parent::__construct();
    }
}
