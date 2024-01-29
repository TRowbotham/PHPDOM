<?php

declare(strict_types=1);

namespace Rowbot\DOM\InternalEvent;

use Rowbot\DOM\Element\Element;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @see https://dom.spec.whatwg.org/#concept-element-attributes-change-ext
 */
class AttributeChangedEvent extends Event
{
    public readonly Element $element;

    public readonly string $localName;

    /**
     * The previous value of the content attribute. This can be null if the content attribute did not previously exist.
     */
    public readonly ?string $oldValue;

    /**
     * The new value of the content attribute. This can be null if the content attribtue is being removed from the
     * Element.
     */
    public readonly ?string $value;

    public readonly ?string $namespace;

    public function __construct(
        Element $element,
        string $localName,
        ?string $oldValue,
        ?string $value,
        ?string $namespace
    ) {
        $this->element = $element;
        $this->localName = $localName;
        $this->oldValue = $oldValue;
        $this->value = $value;
        $this->namespace = $namespace;
    }
}
