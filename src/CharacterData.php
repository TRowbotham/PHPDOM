<?php

declare(strict_types=1);

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
 * @property string $data Represents the textual data contained by this Node.
 *
 * @property-read int                              $length                 Represents the length of the data contained
 *                                                                         by this Node.
 * @property-read \Rowbot\DOM\Element\Element|null $nextElementSibling     Returns the next sibling that is an Element,
 *                                                                         if any.
 * @property-read \Rowbot\DOM\Element\Element|null $previousElementSibling Returns the previous sibling that is an
 *                                                                         Element, if any.
 */
abstract class CharacterData extends Node implements ChildNode
{
    use ChildNodeTrait;
    use NonDocumentTypeChildNode;

    /**
     * @var string
     */
    protected $data;

    public function __construct(Document $document, string $data)
    {
        parent::__construct($document);

        $this->data = $data;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'data':
                return $this->data;

            case 'length':
                return $this->getLength();

            case 'nextElementSibling':
                return $this->getNextElementSibling();

            case 'previousElementSibling':
                return $this->getPreviousElementSibling();

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'data':
                if ($value === null) {
                    $value = '';
                }

                $this->doReplaceData(0, $this->getLength(), (string) $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Appends the given string to the Node's existing string data.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-appenddata
     */
    public function appendData(string $data): void
    {
        $this->doReplaceData($this->getLength(), 0, $data);
    }

    /**
     * Removes the specified number of characters starting from the given
     * offset.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-deletedata
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If the given offset is greater than the length of the data.
     */
    public function deleteData(int $offset, int $count): void
    {
        $this->doReplaceData(Utils::unsignedLong($offset), Utils::unsignedLong($count), '');
    }

    /**
     * Inserts the given string data at the specified offset.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-insertdata
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If the given offset is greater than the length of the data.
     */
    public function insertData(int $offset, string $data): void
    {
        $this->doReplaceData(Utils::unsignedLong($offset), 0, $data);
    }

    /**
     * Replaces a portion of the string with the provided data begining at the
     * given offset and lasting until the given count.
     *
     * @see https://dom.spec.whatwg.org/#dom-characterdata-replacedata
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If the given offset is greater than the length of the data.
     */
    public function replaceData(int $offset, int $count, string $data): void
    {
        $this->doReplaceData(Utils::unsignedLong($offset), Utils::unsignedLong($count), $data);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-cd-replace
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If the given offset is greater than the length of the data.
     */
    public function doReplaceData(int $offset, int $count, string $data): void
    {
        $length = $this->getLength();

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

        $this->data = mb_substr($this->data, 0, $offset, 'utf-8')
            . $data
            . mb_substr($this->data, $offset + $count, $length - $offset, 'utf-8');
        $newDataLen = mb_strlen($data, 'utf-8');

        foreach (Range::getRangeCollection() as $range) {
            $startNode = $range->startContainer;
            $endNode = $range->endContainer;

            if ($startNode === $this) {
                $startOffset = $range->startOffset;

                // 8. For each live range whose start node is node and start offset is greater than
                // offset but less than or equal to offset plus count, set its start offset to
                // offset.
                if ($startOffset > $offset && $startOffset <= $startOffset + $count) {
                    $range->setStartInternal($startNode, $offset);

                // 10. For each live range whose start node is node and start offset is greater than
                // offset plus count, increase its start offset by data’s length and decrease it by
                // count.
                } elseif ($startOffset > $offset + $count) {
                    // If we perform step 8, then we know we can't reach here since range's start
                    // offset is set to $offset and therefore can't be greater than $offset + $count.
                    $range->setStartInternal($startNode, $startOffset + $newDataLen - $count);
                }
            }

            if ($endNode === $this) {
                $endOffset = $range->endOffset;

                // 9. For each live range whose end node is node and end offset is greater than
                // offset but less than or equal to offset plus count, set its end offset to offset.
                if ($endOffset > $offset && $endOffset <= $offset + $count) {
                    $range->setEndInternal($endNode, $offset);

                // 11. For each live range whose end node is node and end offset is greater than
                // offset plus count, increase its end offset by data’s length and decrease it by
                // count.
                } elseif ($endOffset > $offset + $count) {
                    // If we perform step 9, then we know we can't reach here since range's end
                    // offset is set to $offset and therefore can't be greater than $offset + $count.
                    $range->setEndInternal($endNode, $endOffset + $newDataLen - $count);
                }
            }
        }
    }

    /**
     * Returns a portion of the nodes data string starting at the specified
     * offset.
     *
     * @see https://dom.spec.whatwg.org/#concept-CD-substring
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If the given offset is greater than the length of the data.
     */
    public function substringData(int $offset, int $count): string
    {
        $length = $this->getLength();
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
            return mb_substr($this->data, $offset, null, 'utf-8');
        }

        return mb_substr($this->data, $offset, $count, 'utf-8');
    }

    public function getLength(): int
    {
        return mb_strlen($this->data, 'utf-8');
    }

    /**
     * @internal
     */
    public function setData(string $data, bool $append = false): void
    {
        if (!$append) {
            $this->data = $data;

            return;
        }

        $this->data .= $data;
    }

    protected function getNodeValue(): string
    {
        return $this->data;
    }

    protected function setNodeValue(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $this->doReplaceData(0, $this->getLength(), $value);
    }

    protected function getTextContent(): string
    {
        return $this->data;
    }

    protected function setTextContent(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $this->doReplaceData(0, $this->getLength(), $value);
    }
}
