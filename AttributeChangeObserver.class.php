<?php
namespace phpjs;

use phpjs\elements\Element;

/**
 * Runs a set of steps when a content attribute changes for a particular
 * element.
 *
 * @internal
 *
 * @see https://dom.spec.whatwg.org/#concept-element-attributes-change-ext
 *
 * @param Element $aElement The Element whose content attribute changed.
 *
 * @param string $aLocalName The localname of the attribute that changed.
 *
 * @param string|null $aOldValue The previous value of the content attribute.
 *     This can be null if the content attribute did not previously exist.
 *
 * @param string|null $aValue The new value of the content attribute. This can
 *     be null if the content attribtue is being removed from the Element.
 *
 * @param string|null $aNamespace The namespace of the content attribute.
 */
interface AttributeChangeObserver
{
    public function onAttributeChanged(
        Element $aElement,
        $aLocalName,
        $aOldValue,
        $aValue,
        $aNamespace
    );
}
