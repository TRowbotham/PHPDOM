<?php
namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Iterator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Exception\NotFoundError;

/**
 * Represents a list of named attributes.
 *
 * @link https://dom.spec.whatwg.org/#namednodemap
 * @link https://developer.mozilla.org/en-US/docs/Web/API/NamedNodeMap
 *
 * @property-read int $length Returns the number of attributes in the list.
 */
class NamedNodeMap implements ArrayAccess, Countable, Iterator
{
    private $element;

    public function __construct(Element $element)
    {
        $this->element = $element;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'length':
                return $this->element->getAttributeList()->count();
        }
    }

    public function item($index)
    {
        return $this->element->getAttribtueList()->offsetGet($index);
    }

    public function getNamedItem($qualifiedName)
    {
        return $this->element->getAttributeList()->getAttrByName(
            Utils::DOMString($qualifiedName)
        );
    }

    public function getNamedItemNS(?string $namespace, $localName)
    {
        return $this->element->getAttributeList()
            ->getAttrByNamespaceAndLocalName(
            $namespace,
            Utils::DOMString($localName)
        );
    }

    public function setNamedItem(Attr $attr)
    {
        try {
            return $this->element->getAttributeList()->setAttr($attr);
        } catch (DOMException $e) {
            throw $e;
        }
    }

    public function setNamedItemNS(Attr $attr)
    {
        try {
            return $this->element->getAttributeList()->setAttr($attr);
        } catch (DOMException $e) {
            throw $e;
        }
    }

    public function removeNamedItem($qualifiedName)
    {
        $attr = $this->element->getAttributeList()->removeAttrByName(
            Utils::DOMString($qualifiedName)
        );

        if (!$attr) {
            throw new NotFoundError();
        }

        return $attr;
    }

    public function removeNamedItemNS(?string $namespace, $localName)
    {
        $attr = $this->element->getAttributeList()
            ->removeAttrByNamespaceAndLocalName(
            $namespace,
            Utils::DOMString($localName)
        );

        if (!$attr) {
            throw new NotFoundError();
        }

        return $attr;
    }

    public function offsetExists($offset)
    {
        return $this->element->getAttributeList()->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->element->getAttributeList()->offsetGet($offset);
    }

    public function offsetSet($offset, $attr)
    {
        // Do nothing.
    }

    public function offsetUnset($offset)
    {
        // Do nothing.
    }

    public function count()
    {
        return $this->element->getAttributeList()->count();
    }

    public function current()
    {
        return $this->element->getAttributeList()->current();
    }

    public function key()
    {
        return $this->element->getAttributeList()->key();
    }

    public function next()
    {
        $this->element->getAttributeList()->next();
    }

    public function rewind()
    {
        $this->element->getAttributeList()->rewind();
    }

    public function valid()
    {
        return $this->element->getAttributeList()->valid();
    }
}
