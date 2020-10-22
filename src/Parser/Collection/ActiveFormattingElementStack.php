<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Collection;

use Rowbot\DOM\Parser\Bookmark;
use Rowbot\DOM\Parser\Collection\Exception\CollectionException;
use Rowbot\DOM\Parser\Marker;
use Rowbot\DOM\Support\UniquelyIdentifiable;

use function count;

class ActiveFormattingElementStack extends ObjectStack
{
    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#push-onto-the-list-of-active-formatting-elements
     *
     * @return (\Rowbot\DOM\Element\Element&\Rowbot\DOM\Support\UniquelyIdentifiable)|\Rowbot\DOM\Parser\Marker
     */
    public function push(UniquelyIdentifiable $element): void
    {
        $count = 0;

        if ($element instanceof Bookmark || $element instanceof Marker) {
            parent::push($element);

            return;
        }

        for ($i = $this->size - 1; $i >= 0; $i--) {
            if ($this->collection[$i] instanceof Marker) {
                break;
            }

            if ($this->collection[$i] instanceof Bookmark) {
                continue;
            }

            $item = $this->collection[$i];
            $namespace = $element->namespaceURI;
            $tagName = $element->tagName;

            if ($namespace !== $item->namespaceURI || $tagName !== $item->tagName) {
                continue;
            }

            $elementAttributes = $element->getAttributeList();
            $itemAttributes = $item->getAttributeList();

            if (count($elementAttributes) !== count($itemAttributes)) {
                continue;
            }

            foreach ($elementAttributes as $attr) {
                $attrNamespace = $attr->getNamespace();
                $attrName = $attr->getQualifiedName();
                $itemAttr = $itemAttributes->getAttrByNamespaceAndLocalName(
                    $attrNamespace,
                    $attr->getLocalName()
                );

                if (
                    $itemAttr === null
                    || $attrName !== $itemAttr->getQualifiedName()
                    || $attrNamespace !== $itemAttr->getNamespace()
                    || $attr->getValue() !== $itemAttr->getValue()
                ) {
                    continue 2;
                }
            }

            if (++$count === 3) {
                try {
                    parent::push($element);
                } catch (CollectionException $e) {
                    throw $e;

                    return;
                }

                parent::remove($this->collection[$i]);

                return;
            }
        }

        parent::push($element);
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
}
