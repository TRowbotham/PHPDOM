<?php
namespace phpjs;

use phpjs\exceptions\IndexSizeError;

/**
 * Represents a Node that contains characters.
 *
 * @see https://dom.spec.whatwg.org/#characterdata
 * @see https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
 *
 * @property string $data Represents the textual data contained by this Node.
 *
 * @property-read int $length Represents the length of the data contained by
 *     this Node.
 *
 * @property-read Element|null $nextElementSibling Returns the next sibling that
 *     is an Element, if any.
 *
 * @property-read Element|null $previousElementSibling Returns the previous
 *     sibling that is an Element, if any.
 */
abstract class CharacterData extends Node
{
    use ChildNode;
    use NonDocumentTypeChildNode;

    protected $mData;
    protected $mLength;

    public function __construct($aData)
    {
        parent::__construct();

        $this->mData = $aData;
        $this->mLength = mb_strlen($aData, $this->mOwnerDocument->characterSet);
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'data':
                return $this->mData;
            case 'length':
                return $this->mLength;
            case 'nextElementSibling':
                return $this->getNextElementSibling();
            case 'previousElementSibling':
                return $this->getPreviousElementSibling();
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'data':
                $this->doReplaceData(
                    0,
                    $this->mLength,
                    Utils::DOMString($aValue, true)
                );

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
    public function appendData($aData)
    {
        $this->doReplaceData($this->mLength, 0, Utils::DOMString($aData));
    }

    /**
     * Removes the specified number of characters starting from the given
     * offset.
     *
     * @param int $aOffset The offset where data deletion should begin.
     *
     * @param int $aCount How many characters to delete starting from the
     *     given offset.
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function deleteData($aOffset, $aCount)
    {
        $this->doReplaceData(
            Utils::unsignedLong($aOffset),
            Utils::unsignedLong($aCount),
            ''
        );
    }

    /**
     * Inserts the given string data at the specified offset.
     *
     * @param  int      $aOffset The offset where insertion should begin.
     *
     * @param  string   $aData   The string data to be inserted.
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function insertData($aOffset, $aData)
    {
        $this->doReplaceData(
            Utils::unsignedLong($aOffset),
            0,
            Utils::DOMString($aData)
        );
    }

    /**
     * Replaces a portion of the string with the provided data begining at the
     * given offset and lasting until the given count.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-replacedata
     *
     * @param int $aOffset The position within the string where the
     *     replacement should begin.
     *
     * @param int $aCount The number of characters from the given offset that
     *     the replacement should extend to.
     *
     * @param string $aData The data to be inserted in to the string.
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function replaceData($aOffset, $aCount, $aData)
    {
        $this->doReplaceData(
            Utils::unsignedLong($aOffset),
            Utils::unsignedLong($aCount),
            Utils::DOMString($aData)
        );
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-cd-replace
     *
     * @param int $aOffset The position within the string where the
     *     replacement should begin.
     *
     * @param int $aCount The number of characters from the given offset that
     *     the replacement should extend to.
     *
     * @param string $aData The data to be inserted in to the string.
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function doReplaceData($aOffset, $aCount, $aData)
    {
        $length = $this->mLength;
        $count = $aCount;

        if ($aOffset < 0 || $aOffset > $length) {
            throw new IndexSizeError(
                sprintf(
                    'The offset should be less than the length of the data. The
                    offset given is %d and the length of the data is %d.',
                    $aOffset,
                    $length
                )
            );
        }

        if ($aOffset + $count > $length) {
            $count = $length - $aOffset;
        }

        // TODO: Queue a mutation record of "characterData" for node with
        // oldValue node’s data.

        $encoding = $this->mOwnerDocument->characterSet;
        $this->mData = mb_substr($this->mData, 0, $aOffset, $encoding) .
            $aData .
            mb_substr(
                $this->mData,
                $aOffset + $count,
                $length - $aOffset,
                $encoding
            );
        $newDataLen = mb_strlen($aData, $encoding);
        $this->mLength = mb_strlen($this->mData, $encoding);

        $ranges = Range::_getRangeCollection();

        foreach ($ranges as $index => $range) {
            $startContainer = $range->startContainer;
            $startOffset = $range->startOffset;

            if ($startContainer === $this &&
                $startOffset > $aOffset &&
                $startOffset <= $aOffset + $count
            ) {
                $range->setStart($startContainer, $aOffset);
            }
        }

        foreach ($ranges as $index => $range) {
            $endContainer = $range->endContainer;
            $endOffset = $range->endOffset;

            if ($endContainer === $this &&
                $endOffset > $aOffset &&
                $endOffset <= $aOffset + $count
            ) {
                $range->setEnd($endContainer, $aOffset);
            }
        }

        foreach ($ranges as $index => $range) {
            $startContainer = $range->startContainer;
            $startOffset = $range->startOffset;

            if ($startContainer === $this && $startOffset > $aOffset + $count) {
                $range->setStart(
                    $startContainer,
                    $startOffset + $newDataLen - $count
                );
            }
        }

        foreach ($ranges as $index => $range) {
            $endContainer = $range->endContainer;
            $endOffset = $range->endOffset;

            if ($endContainer === $this && $endOffset > $aOffset + $count) {
                $range->setEnd(
                    $endContainer,
                    $endOffset + $newDataLen - $count
                );
            }
        }
    }

    /**
     * Returns a portion of the nodes data string starting at the specified
     * offset.
     *
     * @see https://dom.spec.whatwg.org/#concept-CD-substring
     *
     * @param  int  $aOffset  The position in the string where the substring
     *     should begin.
     *
     * @param  int  $aCount  The number of characters the substring should
     *     include starting from the given offset.
     *
     * @return string
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function substringData($aOffset, $aCount)
    {
        $length = $this->mLength;
        $aOffset = Utils::unsignedLong($aOffset);
        $count = Utils::unsignedLong($aCount);

        if ($aOffset < 0 || $aOffset > $length) {
            throw new IndexSizeError(
                sprintf(
                    'The offset should be less than the length of the data. The
                    offset given is %d and the length of the data is %d.',
                    $aOffset,
                    $length
                )
            );
        }

        if ($aOffset + $count > $length) {
            return mb_substr($this->mData, $aOffset);
        }

        return mb_substr($this->mData, $aOffset, $count);
    }

    /**
     * Returns the Node's length, which is the number of codepoints in the data
     * attribute.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-length
     * @see Node::getLength()
     *
     * @return int
     */
    public function getLength()
    {
        return $this->mLength;
    }

    /**
     * Gets the value of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     * @see Node::getNodeValue()
     *
     * @return string
     */
    protected function getNodeValue()
    {
        return $this->mData;
    }

    /**
     * Sets the node's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     * @see Node::setNodeValue()
     *
     * @param string|null $aNewValue The node's new value.
     */
    protected function setNodeValue($aNewValue)
    {
        $this->doReplaceData(
            0,
            $this->mLength,
            Utils::DOMString($aNewValue, true)
        );
    }

    /**
     * Gets the concatenation of all descendant text nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     * @see Node::getTextContent()
     *
     * @return string
     */
    protected function getTextContent()
    {
        return $this->mData;
    }

    /**
     * Sets the nodes text content.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     * @see Node::setTextContent()
     *
     * @param string|null $aNewValue The new text to be inserted into the node.
     */
    protected function setTextContent($aNewValue)
    {
        $this->doReplaceData(
            0,
            $this->mLength,
            Utils::DOMString($aNewValue, true)
        );
    }
}
