<?php
namespace phpjs;

use phpjs\elements\Element;
use phpjs\exceptions\DOMException;
use phpjs\exceptions\NotFoundError;

/**
 * Represents a list of named attributes.
 *
 * @link https://dom.spec.whatwg.org/#namednodemap
 * @link https://developer.mozilla.org/en-US/docs/Web/API/NamedNodeMap
 *
 * @property-read int $length Returns the number of attributes in the list.
 */
class NamedNodeMap implements \ArrayAccess, \SeekableIterator, \Countable
{
    private $mAttributesList;
    private $mOwnerElement;
    private $mPosition;

    public function __construct(
        Element $aOwnerElement,
        AttributeList $aAttributesList
    ) {
        $this->mAttributesList = $aAttributesList;
        $this->mOwnerElement = $aOwnerElement;
        $this->mPosition = 0;
    }

    public function __destruct()
    {
        $this->mAttributesList = null;
        $this->mOwnerElement = null;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'length':
                return $this->mAttributesList->count();
        }
    }

    public function count()
    {
        return $this->mAttributesList->count();
    }

    public function current()
    {
        return $this->mAttributesList[$this->mPosition];
    }

    public function getNamedItem($aName)
    {
        return $this->mAttributesList->getAttrByName(
            $aName,
            $this->mOwnerElement
        );
    }

    public function getNamedItemNS($aNamespace, $aLocalName)
    {
        return $this->mAttributesList->getAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $this->mOwnerElement
        );
    }

    public function item($aIndex)
    {
        if ($aIndex >= $this->mAttributesList->count()) {
            return null;
        }

        return $this->mAttributesList[$aIndex];
    }

    public function key()
    {
        return $this->mPosition;
    }

    public function next()
    {
        ++$this->mPosition;
    }

    public function offsetSet($aOffset, $aValue)
    {
        // Do nothing.
    }

    public function offsetExists($aOffset)
    {
        return $this->mAttributesList->offsetExists($aOffset);
    }

    public function offsetUnset($aOffset)
    {
        // Do nothing.
    }

    public function offsetGet($aOffset)
    {
        return $this->mAttributesList->offsetGet($aOffset);
    }

    public function removeNamedItem($aName)
    {
        $attr = $this->mAttributesList->removeAttrByName(
            $aName,
            $this->mOwnerElement
        );

        if (!$attr) {
            throw new NotFoundError();
        }

        return $attr;
    }

    public function removeNamedItemNS($aNamespace, $aLocalName)
    {
        $attr = $this->mAttributesList->removeAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $this->mOwnerElement
        );

        if (!$attr) {
            throw new NotFoundError();
        }

        return $attr;
    }

    public function rewind()
    {
        $this->mPosition = 0;
    }

    public function seek($aPosition)
    {
        $this->mPosition = $aPosition;
    }

    public function setNamedItem(Attr $aAttr)
    {
        try {
            return $this->mAttributesList->setAttr(
                $aAttr,
                $this->mOwnerElement
            );
        } catch (DOMException $e) {
            throw $e;
        }
    }

    public function setNamedItemNS(Attr $aAttr)
    {
        try {
            return $this->mAttributesList->setAttr(
                $aAttr,
                $this->mOwnerElement
            );
        } catch (DOMException $e) {
            throw $e;
        }
    }

    public function valid()
    {
        return isset($this->mAttributesList[$this->mPosition]);
    }
}
