<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;
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

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'text':
                parent::__set('textContent', $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
