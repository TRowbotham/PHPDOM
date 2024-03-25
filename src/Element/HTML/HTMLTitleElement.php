<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Text;

class HTMLTitleElement extends HTMLElement
{
    public string $text {
        get {
            $value = '';

            foreach ($this->childNodes_ as $node) {
                if ($node instanceof Text) {
                    $value .= $node->data;
                }
            }

            return $value;
        }
        set {
            $this->textContent = $value;
        }
    }
}
