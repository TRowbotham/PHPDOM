<?php
namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Iterator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\NotFoundError;

/**
 * Represents a list of named attributes.
 *
 * @see https://dom.spec.whatwg.org/#namednodemap
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NamedNodeMap
 *
 * @property-read int $length Returns the number of attributes in the list.
 */
class NamedNodeMap implements ArrayAccess, Countable, Iterator
{
    /**
     * @var \Rowbot\DOM\Element\Element
     */
    private $element;

    /**
     * Constructor.
     *
     * @param \Rowbot\DOM\Element\Element $element
     */
    public function __construct(Element $element)
    {
        $this->element = $element;
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function __get($name)
    {
        switch ($name) {
            case 'length':
                return $this->element->getAttributeList()->count();
        }
    }

    /**
     * Finds the Attr node at the given index.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-item
     *
     * @param int $index
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function item($index)
    {
        return $this->element->getAttributeList()->offsetGet($index);
    }

    /**
     * Finds an Attr node with the given qualified name.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-getnameditem
     *
     * @param string $qualifiedName
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function getNamedItem($qualifiedName)
    {
        return $this->element->getAttributeList()->getAttrByName(
            Utils::DOMString($qualifiedName)
        );
    }

    /**
     * Finds the Attr node with the given namespace and localname.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-getnameditemns
     *
     * @param ?string $namespace
     * @param string  $localName
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function getNamedItemNS(?string $namespace, $localName)
    {
        return $this->element->getAttributeList()
            ->getAttrByNamespaceAndLocalName(
            $namespace,
            Utils::DOMString($localName)
        );
    }

    /**
     * Adds the given attribute to the element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-setnameditem
     *
     * @param \Rowbot\DOM\Attr $attr
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function setNamedItem(Attr $attr)
    {
        return $this->element->getAttributeList()->setAttr($attr);
    }

    /**
     * Adds the given attribute to the element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-setnameditemns
     *
     * @param \Rowbot\DOM\Attr $attr
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function setNamedItemNS(Attr $attr)
    {
        return $this->element->getAttributeList()->setAttr($attr);
    }

    /**
     * Removes the attribute with the given qualified name from the element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-removenameditem
     *
     * @param string $qualifiedName
     *
     * @return \Rowbot\DOM\Attr
     *
     * @throws \Rowbot\DOM\Exception\NotFoundError
     */
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

    /**
     * Removes the attribute with the given qualified name from the element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-removenameditemns
     *
     * @param ?string $namespace
     * @param string  $localName
     *
     * @return \Rowbot\DOM\Attr
     *
     * @throws \Rowbot\DOM\Exception\NotFoundError
     */
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

    /**
     * Indicates whether an attribute node exists at the given offset.
     *
     * @param int $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->element->getAttributeList()->offsetExists($offset);
    }

    /**
     * Returns the attribute node at the given offset.
     *
     * @param int $offset
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function offsetGet($offset)
    {
        return $this->element->getAttributeList()->offsetGet($offset);
    }

    /**
     * Noop.
     *
     * @param int $offset
     * @param \Rowbot\DOM\Attr $attr
     *
     * @return void
     */
    public function offsetSet($offset, $attr)
    {
        // Do nothing.
    }

    /**
     * Noop.
     *
     * @param int $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        // Do nothing.
    }

    /**
     * Returns the number of attribute nodes in the list.
     *
     * @return int
     */
    public function count()
    {
        return $this->element->getAttributeList()->count();
    }

    /**
     * Returns the current attribute node.
     *
     * @return \Rowbot\DOM\Attr
     */
    public function current()
    {
        return $this->element->getAttributeList()->current();
    }

    /**
     * Returns the current iterator key.
     *
     * @return int
     */
    public function key()
    {
        return $this->element->getAttributeList()->key();
    }

    /**
     * Advances the iterator to the next item.
     *
     * @return void
     */
    public function next()
    {
        $this->element->getAttributeList()->next();
    }

    /**
     * Rewinds the iterator to the beginning.
     *
     * @return void
     */
    public function rewind()
    {
        $this->element->getAttributeList()->rewind();
    }

    /**
     * Indicates if the iterator is still valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->element->getAttributeList()->valid();
    }
}
