<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Exception\IndexSizeError;

use function mb_strlen;
use function mb_substr;
use function sprintf;

/**
 * Represents a Node that contains characters.
 *
 * @see https://dom.spec.whatwg.org/#characterdata
 * @see https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
 *
 * @property      string                           $data                   Represents the textual data contained by this
 *                                                                         Node.
 * @property-read int                              $length                 Represents the length of the data contained
 *                                                                         by this Node.
 * @property-read \Rowbot\DOM\Element\Element|null $nextElementSibling     Returns the next sibling that is an Element,
 *                                                                         if any.
 * @property-read \Rowbot\DOM\Element\Element|null $previousElementSibling Returns the previous sibling that is an
 *                                                                         Element, if any.
 */
abstract class CharacterData extends Node
{
    use ChildNode;
    use NonDocumentTypeChildNode;

    protected $data;
    protected $length;

    /**
     * Constructor.
     *
     * @param string $data
     *
     * @return void
     */
    public function __construct(string $data)
    {
        parent::__construct();

        $this->data = $data;
        $this->length = mb_strlen($data, $this->nodeDocument->characterSet);
    }

    /**
     * {@inheritDoc}
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'data':
                return $this->data;
            case 'length':
                return $this->length;
            case 'nextElementSibling':
                return $this->getNextElementSibling();
            case 'previousElementSibling':
                return $this->getPreviousElementSibling();
            default:
                return parent::__get($name);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __set(string $name, $value)
    {
        switch ($name) {
            case 'data':
                if ($value === null) {
                    $value = '';
                }

                $this->doReplaceData(0, $this->length, $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Appends the given string to the Node's existing string data.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-appenddata
     *
     * @param string $data The string data to be appended to the Node.
     *
     * @return void
     */
    public function appendData(string $data): void
    {
        $this->doReplaceData($this->length, 0, $data);
    }

    /**
     * Removes the specified number of characters starting from the given
     * offset.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-deletedata
     *
     * @param int $offset The offset where data deletion should begin.
     * @param int $count  How many characters to delete starting from the given offset.
     *
     * @return void
     *
     * @throws IndexSizeError If the given offset is greater than the length of the data.
     */
    public function deleteData(int $offset, $count): void
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
     * @see https://dom.spec.whatwg.org/#dom-characterdata-insertdata
     *
     * @param int    $offset The offset where insertion should begin.
     * @param string $data   The string data to be inserted.
     *
     * @return void
     *
     * @throws IndexSizeError If the given offset is greater than the length of the data.
     */
    public function insertData(int $offset, string $data): void
    {
        $this->doReplaceData(
            Utils::unsignedLong($offset),
            0,
            $data
        );
    }

    /**
     * Replaces a portion of the string with the provided data begining at the
     * given offset and lasting until the given count.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-replacedata
     *
     * @param int    $offset The position within the string where the replacement should begin.
     * @param int    $count  The number of characters from the given offset that the replacement should extend to.
     * @param string $data   The data to be inserted in to the string.
     *
     * @return void
     *
     * @throws IndexSizeError If the given offset is greater than the length of the data.
     */
    public function replaceData(int $offset, int $count, string $data): void
    {
        $this->doReplaceData(
            Utils::unsignedLong($offset),
            Utils::unsignedLong($count),
            $data
        );
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-cd-replace
     *
     * @param int $offset The position within the string where the replacement should begin.
     * @param int $count The number of characters from the given offset that the replacement should extend to.
     * @param string $data The data to be inserted in to the string.
     *
     * @return void
     *
     * @throws IndexSizeError If the given offset is greater than the length of the data.
     */
    public function doReplaceData(int $offset, int $count, string $data): void
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
     * @param int $offset The position in the string where the substring should begin.
     * @param int $count  The number of characters the substring should include starting from the given offset.
     *
     * @return string
     *
     * @throws IndexSizeError If the given offset is greater than the length of the data.
     */
    public function substringData(int $offset, int $count): string
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
     * {@inheritDoc}
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeValue(): string
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    protected function setNodeValue(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $this->doReplaceData(0, $this->length, $value);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTextContent(): string
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    protected function setTextContent(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $this->doReplaceData(0, $this->length, $value);
    }
}
