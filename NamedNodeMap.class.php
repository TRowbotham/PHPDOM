<?php
/**
 * Represents a list of named attributes.
 *
 * @link https://dom.spec.whatwg.org/#namednodemap
 * @link https://developer.mozilla.org/en-US/docs/Web/API/NamedNodeMap
 *
 * @property-read int $length Returns the number of attributes in the list.
 */
class NamedNodeMap implements ArrayAccess, SeekableIterator, Countable {
    private $mAttributesList;
    private $mOwnerElement;
    private $mPosition;

    public function __construct(Element $aOwnerElement, &$aAttributesList) {
        $this->mAttributesList = &$aAttributesList;
        $this->mOwnerElement = $aOwnerElement;
        $this->mPosition = 0;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'length':
                return count($this->mAttributesList);
        }
    }

    public function count() {
        return count($this->mAttributesList);
    }

    public function current() {
        return $this->mAttributesList[$this->mPosition];
    }

    public function getNamedItem($aName) {
        return $this->mOwnerElement->_getAttributeByName($aName);
    }

    public function getNamedItemNS($aNamespace, $aLocalName) {
        return $this->mOwnerElement->_getAttributeByNamespaceAndLocalName($aNamespace, $aLocalName);
    }

    public function item($aIndex) {
        if ($aIndex >= count($this->mAttributesList)) {
            return null;
        }

        return $this->mAttributesList[$aIndex];
    }

    public function key() {
        return $this->mPosition;
    }

    public function next() {
        ++$this->mPosition;
    }

    public function offsetSet($aOffset, $aValue) {
        // Do nothing.
    }

    public function offsetExists($aOffset) {
        return isset($this->mAttributesList[$aOffset]);
    }

    public function offsetUnset($aOffset) {
        // Do nothing.
    }

    public function offsetGet($aOffset) {
        return isset($this->mAttributesList[$aOffset]) ? $this->mAttributesList[$aOffset] : null;
    }

    public function removeNamedItem($aName) {
        $attr = $this->mOwnerElement->_removeAttributeByName($aName);

        if (!$attr) {
            throw new NotFoundError;
        }

        return $attr;
    }

    public function removeNamedItemNS($aNamespace, $aLocalName) {
        $attr = $this->mOwnerElement->_removeAttributeByNamespaceAndLocalName($aNamespace, $aLocalName);

        if (!$attr) {
            throw new NotFoundError;
        }

        return $attr;
    }

    public function rewind() {
        $this->mPosition = 0;
    }

    public function seek($aPosition) {
        $this->mPosition = $aPosition;
    }

    public function setNamedItem(Attr $aAttr) {
        return $this->mOwnerElement->_setAttribute($aAttr);
    }

    public function setNamedItemNS(Attr $aAttr) {
        return $this->mOwnerElement->_setAttribute($aAttr, $aAttr->namespaceURI, $aAttr->localName);
    }

    public function valid() {
        return isset($this->mAttributesList[$this->mPosition]);
    }
}
