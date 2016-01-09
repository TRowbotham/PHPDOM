<?php
namespace phpjs;

require_once 'Node.class.php';
require_once 'ChildNode.class.php';
require_once 'NonDocumentTypeChildNode.class.php';

/**
 * Represents a Node that contains characters.
 *
 * @see https://dom.spec.whatwg.org/#characterdata
 * @see https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
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
    protected $mLength;

    public function __construct($aData) {
        parent::__construct();

        $this->mData = $aData;
        $this->mLength = strlen($aData);
    }

    public function __get($aName) {
        switch ($aName) {
            case 'data':
                return $this->mData;
            case 'length':
                return $this->mLength;
            case 'nextElementSibling':
                return $this->getNextElementSibling();
            case 'nodeValue':
                return $this->mData;
            case 'previousElementSibling':
                return $this->getPreviousElementSibling();
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'data':
            case 'nodeValue':
                $this->replaceData(0, $this->mLength, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    /**
     * Appends the given string to the Node's existing string data.
     *
     * @param  string $aData The string data to be appended to the Node.
     */
    public function appendData($aData) {
        $this->replaceData($this->mLength, 0, $aData);
    }

    /**
     * Removes the specified number of characters starting from the given offset.
     *
     * @param  int $aOffset The offset where data deletion should begin.
     *
     * @param  int $aCount  How many characters to delete starting from the given offset.
     */
    public function deleteData($aOffset, $aCount) {
        $this->replaceData($aOffset, $aCount, '');
    }

    /**
     * Inserts the given string data at the specified offset.
     *
     * @param  int      $aOffset The offset where insertion should begin.
     *
     * @param  string   $aData   The string data to be inserted.
     */
    public function insertData($aOffset, $aData) {
        $this->replaceData($aOffset, 0, $aData);
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
        $length = $this->mLength;
        $count = $aCount;

        if ($aOffset > $length) {
            throw new IndexSizeError;
        }

        if ($aOffset + $count > $length) {
            $count = $length - $aOffset;
        }

        // TODO: Queue a mutation record for "characterData"
        $this->mData = substr_replace($this->mData, $aData, $aOffset, 0);
        $newDataLen = strlen($aData);
        $this->mLength = $newDataLen + strlen($this->mData);
        $deleteOffset = $aOffset + $newDataLen;
        $this->mData = substr($this->mData, 0, $deleteOffset);

        $ranges = Range::_getRangeCollection();

        foreach ($ranges as $index => $range) {
            $startContainer = $range->startContainer;
            $startOffset = $range->startOffset;

            if ($startContainer === $this && $startOffset > $aOffset && $startOffset <= $aOffset + $count) {
                $range->setStart($startContainer, $aOffset);
            }
        }

        foreach ($ranges as $index => $range) {
            $endContainer = $range->endContainer;
            $endOffset = $range->endOffset;

            if ($endContainer === $this && $endOffset > $aOffset && $endOffset <= $aOffset + $count) {
                $range->setEnd($endContainer, $aOffset);
            }
        }

        foreach ($ranges as $index => $range) {
            $startContainer = $range->startContainer;
            $startOffset = $range->startOffset;

            if ($startContainer === $this && $startOffset > $aOffset + $count) {
                $range->setStart($startContainer, $startOffset + $newDataLen - $count);
            }
        }

        foreach ($ranges as $index => $range) {
            $endContainer = $range->endContainer;
            $endOffset = $range->endOffset;

            if ($endContainer === $this && $endOffset > $aOffset + $count) {
                $range->setEnd($endContainer, $endOffset + $newDataLen - $count);
            }
        }
    }

    /**
     * Returns a portion of the nodes data string starting at the specified
     * offset.
     *
     * @link   https://dom.spec.whatwg.org/#concept-CD-substring
     *
     * @param  int      $aOffset The position in the string where the substring should begin.
     *
     * @param  int      $aCount  The number of characters the substring should include starting from
     *                           the given offset.
     *
     * @return string
     *
     * @throws IndexSizeError
     */
    public function substringData($aOffset, $aCount) {
        $length = $this->mLength;

        if ($aOffset > $length) {
            throw new IndexSizeError;
        }

        if ($aOffset + $aCount > $length) {
            return substr($this->mData, $aOffset);
        }

        return substr($this->mData, $aOffset, $aOffset + $aCount);
    }

    /**
     * Returns the Node's length.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-node-length
     *
     * @return int
     */
    public function _getNodeLength() {
        return $this->mLength;
    }
}
