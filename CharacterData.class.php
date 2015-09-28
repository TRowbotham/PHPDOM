<?php
// https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
// https://dom.spec.whatwg.org/#characterdata

require_once 'Node.class.php';
require_once 'ChildNode.class.php';
require_once 'NonDocumentTypeChildNode.class.php';

/**
 * Represents a Node that contains characters.
 *
 * @link https://dom.spec.whatwg.org/#characterdata
 * @link https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
 *
 * @property        string          $data                       Represents the textual data contained by this Node.
 *
 * @property-read   int             $length                     Represents the length of the data contained by this Node.
 *
 * @property-read   Element|null    $nextElementSibling         Returns the next sibling that is an Element, if any.
 *
 * @property-read   Element|null    $previousElementSibling     Returns the previous sibling that is an Element, if any.
 */
abstract class CharacterData extends Node {
    use ChildNode, NonDocumentTypeChildNode;

    protected $mData;

    public function __construct() {
        parent::__construct();

        $this->mData = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'data':
                return $this->mData;
            case 'length':
                return strlen($this->mData);
            case 'nextElementSibling':
                return $this->getNextElementSibling();
            case 'previousElementSibling':
                return $this->getPreviousElementSibling();
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'data':
                if (is_string($aValue)) {
                    $this->mData = $aValue;
                }

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function appendData($aData) {
        // TODO
    }

    public function deleteData($aOffset, $aCount) {
        // TODO
    }

    public function insertData($aOffset, $aData) {
        // TODO
    }

    /**
     * Replaces a portion of the string with the provided data begining at the given
     * offset and lasting until the given count.
     *
     * @link   https://dom.spec.whatwg.org/#concept-CD-replace
     *
     * @param  int      $aOffset The position within the string where the replacement should begin.
     *
     * @param  int      $aCount  The number of characters from the given offset that the replacement should
     *                           extend to.
     *
     * @param  string   $aData   The data to be inserted in to the string.
     *
     * @throws IndexSizeError
     */
    public function replaceData($aOffset, $aCount, $aData) {
        $length = $this->length;
        $count = $aCount;

        if ($aOffset > $length) {
            throw new IndexSizeError;
        }

        if ($aOffset + $count > $length) {
            $count = $length - $aOffset;
        }

        // TODO: Queue a mutation record for "characterData"

        $this->mData = substr_replace($this->mData, $aData, $aOffset);
        $deleteOffset = $aOffset + $length;
        $this->mData = substr($this->mData, 0, $deleteOffset);

        foreach (Range::_getRangeCollection() as $index => $range ) {
            if ($range->startContainer === $this && $range->startOffset > $aOffset && $range->startOffset <= $aOffset + $count) {
                $range->setStart($range->startContainer, $aOffset);
            }
        }

        foreach (Range::_getRangeCollection() as $index => $range) {
            if ($range->endContainer === $this && $range->endOffset > $aOffset && $range->endOffset <= $aOffset + $count) {
                $range->setEnd($range->endContainer, $aOffset);
            }
        }

        foreach (Range::_getRangeCollection() as $index => $range) {
            if ($range->startContainer === $this && $range->startOffset > $aOffset + $count) {
                $range->setStart($range->startContainer, $range->startOffset + strlen($aData) - $count);
            }
        }

        foreach (Range::_getRangeCollection() as $index => $range) {
            if ($range->endContainer === $this && $range->endOffset > $aOffset + $count) {
                $range->setEnd($range->endContainer, $range->endOffset + strlen($aData) - $count);
            }
        }
    }

    public function substringData($aOffset, $aCount) {
        // TODO
    }
}
