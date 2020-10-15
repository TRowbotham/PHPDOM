<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

/**
 * Runs a set of steps when a content attribute changes for a particular
 * element.
 *
 * @internal
 *
 * @see https://dom.spec.whatwg.org/#concept-element-attributes-change-ext
 *
 * @param \Rowbot\DOM\Element\Element $element   The Element whose content attribute changed.
 * @param string                      $localName The localname of the attribute that changed.
 * @param ?string                     $oldValue  The previous value of the content attribute. This can be null if the
 *                                               content attribute did not previously exist.
 * @param ?string                     $value     The new value of the content attribute. This can be null if the content
 *                                               attribtue is being removed from the Element.
 * @param ?string                     $namespace The namespace of the content attribute.
 */
interface AttributeChangeObserver
{
    public function onAttributeChanged(
        Element $element,
        string $localName,
        ?string $oldValue,
        ?string $value,
        ?string $namespace
    ): void;
}
