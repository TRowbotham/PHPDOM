<?php
namespace Rowbot\DOM\Support;

use function array_push;
use function array_unshift;
use function count;
use function preg_split;

class CodePointStream
{
    const SEEK_RELATIVE = 1;
    const SEEK_ABSOLUTE = 2;

    /**
     * @var string[]
     */
    private $data;

    /**
     * @var int
     */
    private $currentChar;

    /**
     * @var int
     */
    private $nextChar;

    /**
     * Constructor.
     *
     * @param string $data
     *
     * @return void
     */
    public function __construct($data = '')
    {
        $this->currentChar = 0;
        $this->nextChar = 0;

        if ($data) {
            $this->data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);
            return;
        }

        $this->data = [];
    }

    /**
     * Appends data to the stream.
     *
     * @param string $data
     *
     * @return void
     */
    public function append($data): void
    {
        $data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($data)) {
            return;
        }

        array_push($this->data, ...$data);
    }

    /**
     * Appends data to the stream.
     *
     * @param string $data
     *
     * @return void
     */
    public function prepend($data): void
    {
        $data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($data)) {
            return;
        }

        array_unshift($this->data, ...$data);
    }

    /**
     * Returns $count characters starting from the next character.
     *
     * @param int $count
     *
     * @return string
     */
    public function get(int $count = 1): string
    {
        $this->currentChar = $this->nextChar;

        if (!isset($this->data[$this->currentChar])) {
            ++$this->nextChar;
            return '';
        }

        if ($count == 1) {
            ++$this->nextChar;
            return $this->data[$this->currentChar];
        }

        $str = '';

        while ($count--) {
            $str .= $this->data[$this->nextChar];

            if (!isset($this->data[++$this->nextChar])) {
                break;
            }
        }

        return $str;
    }

    /**
     * Gets $count characters starting from the next character without advancing the iterator.
     *
     * @param int $count
     *
     * @return string
     */
    public function peek(int $count = 1): string
    {
        if (!isset($this->data[$this->nextChar])) {
            return '';
        }

        if ($count == 1) {
            return $this->data[$this->nextChar];
        }

        $position = $this->nextChar;
        $str = '';

        while ($count--) {
            $str .= $this->data[$position++];

            if (!isset($this->data[$position])) {
                break;
            }
        }

        return $str;
    }

    /**
     * Returns whether the stream has reached the end.
     *
     * @return bool
     */
    public function isEoS(): bool
    {
        return !isset($this->data[$this->currentChar]);
    }

    /**
     * Returns the number of characters in the stream.
     *
     * @return int
     */
    public function length(): int
    {
        return count($this->data);
    }

    /**
     * Empties the stream.
     *
     * @return void
     */
    public function discard(): void
    {
        $this->data = [];
    }

    /**
     * Seeks to the given position in the stream.
     *
     * @param int $count The count
     *
     * @return void
     */
    public function seek(int $count): void
    {
        $this->nextChar += $count;
        $this->currentChar = $this->nextChar;
    }

    /**
     * Rewinds the steam to the beginning.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->currentChar = 0;
    }
}
