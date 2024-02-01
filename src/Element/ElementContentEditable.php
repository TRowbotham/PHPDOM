<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Exception\SyntaxError;

use function in_array;
use function is_string;
use function strtolower;

/**
 * @see https://html.spec.whatwg.org/multipage/interaction.html#elementcontenteditable
 */
trait ElementContentEditable
{
    private const CONTENT_EDITABLE_STATE_MAP = [
        'true' => ['', 'true'],
        'false' => ['false'],
    ];

    /**
     * @see https://html.spec.whatwg.org/multipage/interaction.html#dom-contenteditable
     */
    #[Getter('contentEditable')]
    private function getContentEditable(): string
    {
        $state = $this->reflectEnumeratedStringAttributeValue(
            'contenteditable',
            'inherit',
            'inherit',
            self::CONTENT_EDITABLE_STATE_MAP
        );

        if ($state === 'true' || $state === '') {
            return 'true';
        }

        if ($state === 'false') {
            return 'false';
        }

        return 'inherit';
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/interaction.html#dom-contenteditable
     */
    #[Setter('contentEditable')]
    private function setContentEditable(mixed $value): void
    {
        if (!is_string($value) || !in_array(strtolower($value), ['true', 'false', 'inherit'], true)) {
            throw new SyntaxError('The value must be one of "true", "false", or "inherit".');
        }

        $value = strtolower($value);

        if ($value === 'inherit') {
            $this->attributeList->removeAttrByNamespaceAndLocalName(null, 'contenteditable');
        } elseif ($value === 'true' || $value === 'false') {
            $this->attributeList->setAttrValue('contenteditable', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/interaction.html#dom-iscontenteditable
     */
    #[Getter('isContentEditable')]
    private function isContentEditable(): bool
    {
        $state = null;
        $node = $this;

        do {
            $state = $node->reflectEnumeratedStringAttributeValue(
                'contenteditable',
                'inherit',
                'inherit',
                self::CONTENT_EDITABLE_STATE_MAP
            );
            $node = $node->parentNode;
        } while ($state === 'inherit' && $node instanceof self);

        return in_array($state, self::CONTENT_EDITABLE_STATE_MAP['true'], true);
    }
}
