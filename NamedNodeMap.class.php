<?php
class NamedNodeMap implements ArrayAccess, SeekableIterator, Countable {
    private $mAttributes;
    private $mLength;
    private $mPosition;

    public function __construct() {
        $this->mAttributes = array();
        $this->mLength = 0;
        $this->mPosition = 0;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'length':
                return $this->mLength;
        }
    }

    public function count() {
        return $this->mLength;
    }

    public function current() {
        return $this->mAttributes[$this->mPosition];
    }

    public function getNamedItem($aName) {
        foreach($this->mAttributes as $attr) {
            if ($attr->nodeName == $aName) {
                return $attr;
            }
        }

        return null;
    }

    public function getNamedItemNS($aNamespaceURI, $aLocalName) {
        foreach($this->mAttributes as &$attr) {
            if ($attr->nodeName == $aLocalName &&
                $attr->namespaceURI == $aNamespaceURI) {
                return $attr;
            }
        }

        return null;
    }

    public function item($aIndex) {
        if (array_key_exists($aIndex, $this->mAttributes)) {
            return $this->mAttributes[$aIndex];
        }

        return null;
    }

    public function key() {
        return $this->mPosition;
    }

    public function next() {
        ++$this->mPosition;
    }

    public function offsetSet($aOffset, $aValue) {
        $this->mLength++;

        if (is_null($aOffset)) {
            $this->mAttributes[] = $aValue;
        } else {
            $this->mAttributes[$aOffset] = $value;
        }
    }

    public function offsetExists($aOffset) {
        return isset($this->mAttributes[$aOffset]);
    }

    public function offsetUnset($aOffset) {
        $this->mLength--;
        array_splice($this->mAttributes, $aOffset, 1);
    }

    public function offsetGet($aOffset) {
        return isset($this->mAttributes[$aOffset]) ? $this->mAttributes[$aOffset] : null;
    }

    public function seek($aPosition) {
        $this->mPosition = $aPosition;
    }

    public function setNamedItem(Attr $aNode) {
        $this->mAttributes[] = $aNode;
        $this->mLength++;
    }

    public function setNamedItemNS(Attr $aNode) {
        $this->mAttributes[] = $aNode;
        $this->mLength++;
    }

    public function removeNamedItem($aName) {
        foreach($this->mAttributes as $attr) {
            if ($attr->nodeName == $aName) {
                $this->mLength--;

                return array_splice($this->mAttributes, key($this->mAttributes), 1);
            }
        }

        return null;
    }

    public function removeNamedItemNS($aNamespaceURI, $aLocalName) {
        foreach($this->mAttributes as $attr) {
            if ($attr->nodeName == $aLocalName &&
                $attr->namespaceURI == $aNamespaceURI) {
                $this->mLength--;

                return array_splice($this->mAttributes, key($this->mAttributes), 1);
            }
        }

        return null;
    }

    public function rewind() {
        $this->mPosition = 0;
    }

    public function valid() {
        return isset($this->mAttributes[$this->mPosition]);
    }
}