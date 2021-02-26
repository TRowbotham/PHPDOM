<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Collection;

use Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException;
use Rowbot\DOM\Parser\Marker;

use function array_splice;
use function count;

/**
 * @extends \Rowbot\DOM\Parser\Collection\ObjectStack<\Rowbot\DOM\Element\HTML\HTMLElement|\Rowbot\DOM\Parser\Marker>
 */
class ActiveFormattingElementStack extends ObjectStack
{
    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#push-onto-the-list-of-active-formatting-elements
     *
     * @param \Rowbot\DOM\Element\HTML\HTMLElement $item
     */
    public function push($item): void
    {
        $count = 0;

        // 1. If there are already three elements in the list of active formatting elements after
        // the last marker, if any, or anywhere in the list if there are no markers, that have the
        // same tag name, namespace, and attributes as element, then remove the earliest such
        // element from the list of active formatting elements. For these purposes, the attributes
        // must be compared as they were when the elements were created by the parser; two elements
        // have the same attributes if all their parsed attributes can be paired such that the two
        // attributes in each pair have identical names, namespaces, and values (the order of the
        // attributes does not matter).
        for ($i = $this->size - 1; $i >= 0; --$i) {
            $element = $this->stack[$i];

            if ($element instanceof Marker) {
                break;
            }

            if (
                $element->namespaceURI !== $item->namespaceURI
                || $element->tagName !== $item->tagName
            ) {
                continue;
            }

            $elementAttributes = $element->getAttributeList();
            $itemAttributes = $item->getAttributeList();

            if (count($elementAttributes) !== count($itemAttributes)) {
                continue;
            }

            foreach ($elementAttributes as $attr) {
                $attrNamespace = $attr->getNamespace();
                $itemAttr = $itemAttributes->getAttrByNamespaceAndLocalName(
                    $attrNamespace,
                    $attr->getLocalName()
                );

                if (
                    $itemAttr === null
                    || $attr->getQualifiedName() !== $itemAttr->getQualifiedName()
                    || $attr->getNamespace() !== $itemAttr->getNamespace()
                    || $attr->getValue() !== $itemAttr->getValue()
                ) {
                    continue 2;
                }
            }

            if (++$count === 3) {
                parent::remove($element);

                break;
            }
        }

        // 2. Add element to the list of active formatting elements.
        parent::push($item);
    }

    /**
     * Pops all entries in the list of active formatting elements up to the
     * next marker.
     *
     * @see https://html.spec.whatwg.org/multipage/#clear-the-list-of-active-formatting-elements-up-to-the-last-marker
     */
    public function clearUpToLastMarker(): void
    {
        $size = $this->size;

        while ($size--) {
            if (parent::pop() instanceof Marker) {
                break;
            }
        }
    }

    /**
     * @param \Rowbot\DOM\Element\HTML\HTMLElement $item
     */
    public function insertAt(int $index, $item): void
    {
        if ($this->cache->contains($item)) {
            throw new DuplicateItemException();
        }

        array_splice($this->stack, $index, 0, [$item]);
        $this->cache->attach($item);
        ++$this->size;
    }

    public function insertMarker(): void
    {
        $marker = new Marker();
        $this->stack[] = $marker;
        $this->cache->attach($marker);
        ++$this->size;
    }
}
