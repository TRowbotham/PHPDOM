<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\Resettable;

/**
 * @see https://html.spec.whatwg.org/multipage/forms.html#the-output-element
 */
class HTMLOutputElement extends HTMLElement implements Resettable
{
    protected function __construct()
    {
        parent::__construct();
    }
}
