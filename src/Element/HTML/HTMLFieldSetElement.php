<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\Listable;

/**
 * @see https://html.spec.whatwg.org/multipage/forms.html#the-fieldset-element
 */
class HTMLFieldSetElement extends HTMLElement implements Listable
{
    protected function __construct()
    {
        parent::__construct();
    }
}
