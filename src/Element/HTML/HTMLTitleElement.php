<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Text;

class HTMLTitleElement extends HTMLElement
{
    #[Getter('text')]
    private function getText(): string
    {
        $value = '';

        foreach ($this->childNodes_ as $node) {
            if ($node instanceof Text) {
                $value .= $node->data;
            }
        }

        return $value;
    }

    #[Setter('text')]
    private function setText(mixed $value): void
    {
        $this->setTextContent($value);
    }
}
