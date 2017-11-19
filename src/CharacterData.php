<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Exception\IndexSizeError;

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

    protected $data;
    protected $length;

    public function __construct($data)
    {
        parent::__construct();

        $this->data = $data;
        $this->length = mb_strlen($data, $this->nodeDocument->characterSet);
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'data':
                return $this->data;
            case 'length':
                return $this->length;
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
                    $this->length,
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
     * @param  string $data The string data to be appended to the Node.
     */
    public function appendData($data)
    {
        $this->doReplaceData($this->length, 0, Utils::DOMString($data));
    }

    /**
     * Removes the specified number of characters starting from the given
     * offset.
     *
     * @param int $offset The offset where data deletion should begin.
     *
     * @param int $count How many characters to delete starting from the
     *     given offset.
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function deleteData($offset, $count)
    {
        $this->doReplaceData(
            Utils::unsignedLong($offset),
            Utils::unsignedLong($count),
            ''
        );
    }

    /**
     * Inserts the given string data at the specified offset.
     *
     * @param  int      $offset The offset where insertion should begin.
     *
     * @param  string   $data   The string data to be inserted.
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function insertData($offset, $data)
    {
        $this->doReplaceData(
            Utils::unsignedLong($offset),
            0,
            Utils::DOMString($data)
        );
    }

    /**
     * Replaces a portion of the string with the provided data begining at the
     * given offset and lasting until the given count.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-replacedata
     *
     * @param int $offset The position within the string where the
     *     replacement should begin.
     *
     * @param int $count The number of characters from the given offset that
     *     the replacement should extend to.
     *
     * @param string $data The data to be inserted in to the string.
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function replaceData($offset, $count, $data)
    {
        $this->doReplaceData(
            Utils::unsignedLong($offset),
            Utils::unsignedLong($count),
            Utils::DOMString($data)
        );
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-cd-replace
     *
     * @param int $offset The position within the string where the
     *     replacement should begin.
     *
     * @param int $count The number of characters from the given offset that
     *     the replacement should extend to.
     *
     * @param string $data The data to be inserted in to the string.
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function doReplaceData($offset, $count, $data)
    {
        $length = $this->length;

        if ($offset < 0 || $offset > $length) {
            throw new IndexSizeError(sprintf(
                'The offset should be less than the length of the data. The'
                . 'offset given is %d and the length of the data is %d.',
                $offset,
                $length
            ));
        }

        if ($offset + $count > $length) {
            $count = $length - $offset;
        }

        // TODO: Queue a mutation record of "characterData" for node with
        // oldValue node’s data.

        $encoding = $this->nodeDocument->characterSet;
        $this->data = mb_substr($this->data, 0, $offset, $encoding)
            . $data
            . mb_substr(
                $this->data,
                $offset + $count,
                $length - $offset,
                $encoding
            );
        $newDataLen = mb_strlen($data, $encoding);
        $this->length += $newDataLen - $count;

        $ranges = Range::getRangeCollection();

        foreach ($ranges as $index => $range) {
            $startContainer = $range->startContainer;
            $startOffset = $range->startOffset;

            if ($startContainer === $this
                && $startOffset > $offset
                && $startOffset <= $offset + $count
            ) {
                $range->setStart($startContainer, $offset);
            }
        }

        foreach ($ranges as $index => $range) {
            $endContainer = $range->endContainer;
            $endOffset = $range->endOffset;

            if ($endContainer === $this
                && $endOffset > $offset
                && $endOffset <= $offset + $count
            ) {
                $range->setEnd($endContainer, $offset);
            }
        }

        foreach ($ranges as $index => $range) {
            $startContainer = $range->startContainer;
            $startOffset = $range->startOffset;

            if ($startContainer === $this && $startOffset > $offset + $count) {
                $range->setStart(
                    $startContainer,
                    $startOffset + $newDataLen - $count
                );
            }
        }

        foreach ($ranges as $index => $range) {
            $endContainer = $range->endContainer;
            $endOffset = $range->endOffset;

            if ($endContainer === $this && $endOffset > $offset + $count) {
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
     * @param  int  $offset  The position in the string where the substring
     *     should begin.
     *
     * @param  int  $count  The number of characters the substring should
     *     include starting from the given offset.
     *
     * @return string
     *
     * @throws IndexSizeError If the given offset is greater than the length
     *     of the data.
     */
    public function substringData($offset, $count)
    {
        $length = $this->length;
        $offset = Utils::unsignedLong($offset);
        $count = Utils::unsignedLong($count);

        if ($offset < 0 || $offset > $length) {
            throw new IndexSizeError(sprintf(
                'The offset should be less than the length of the data. The'
                . 'offset given is %d and the length of the data is %d.',
                $offset,
                $length
            ));
        }

        if ($offset + $count > $length) {
            return mb_substr($this->data, $offset);
        }

        return mb_substr($this->data, $offset, $count);
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
        return $this->length;
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
        return $this->data;
    }

    /**
     * Sets the node's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     * @see Node::setNodeValue()
     *
     * @param string|null $newValue The node's new value.
     */
    protected function setNodeValue($newValue)
    {
        $this->doReplaceData(
            0,
            $this->length,
            Utils::DOMString($newValue, true)
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
        return $this->data;
    }

    /**
     * Sets the nodes text content.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     * @see Node::setTextContent()
     *
     * @param string|null $newValue The new text to be inserted into the node.
     */
    protected function setTextContent($newValue)
    {
        $this->doReplaceData(
            0,
            $this->length,
            Utils::DOMString($newValue, true)
        );
    }
}
