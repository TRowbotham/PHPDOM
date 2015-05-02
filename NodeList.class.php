<?php
// https://developer.mozilla.org/en-US/docs/Web/API/NodeList
// https://dom.spec.whatwg.org/#interface-nodelist

class NodeList implements ArrayAccess, SeekableIterator, Countable {
	private $mLength;
	private $mNodes;
	private $mPosition;

	public function __construct() {
		$this->mLength = 0;
		$this->mNodes = array();
		$this->mPosition = 0;
	}

	public function __get($aName) {
		switch($aName) {
			case 'length':
				return $this->mLength;
		}
	}

	public function count() {
        return $this->mLength;
    }

    public function current() {
        return $this->mNodes[$this->mPosition];
    }

	public function item($aIndex) {
        if (array_key_exists($aIndex, $this->mNodes)) {
            return $this->mNodes[$aIndex];
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
            $this->mNodes[] = $aValue;
        } else {
            $this->mNodes[$aOffset] = $value;
        }
    }

    public function offsetExists($aOffset) {
        return isset($this->mNodes[$aOffset]);
    }

    public function offsetUnset($aOffset) {
        unset($this->mNodes[$aOffset]);
    }

    public function offsetGet($aOffset) {
        return isset($this->mNodes[$aOffset]) ? $this->mNodes[$aOffset] : null;
    }

    public function seek($aPosition) {
        $this->mPosition = $aPosition;
    }

    public function rewind() {
        $this->mPosition = 0;
    }

    public function valid() {
        return isset($this->mNodes[$this->mPosition]);
    }
}