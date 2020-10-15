<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\Listable;
use Rowbot\DOM\Element\HTML\Support\Submittable;

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-object-element
 */
class HTMLObjectElement extends HTMLElement implements Listable, Submittable
{
}
