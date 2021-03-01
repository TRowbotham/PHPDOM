<?php

declare(strict_types=1);

namespace Rowbot\DOM\Support;

use function array_push;
use function array_unshift;
use function count;
use function preg_split;

use const PREG_SPLIT_NO_EMPTY;

class CodePointStream
{
    public const SEEK_RELATIVE = 1;
    public const SEEK_ABSOLUTE = 2;

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

    public function __construct(string $data = '')
    {
        $this->currentChar = 0;
        $this->nextChar = 0;

        if ($data !== '') {
            $data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

            if ($data === [] || $data === false) {
                return;
            }

            $this->data = $data;

            return;
        }

        $this->data = [];
    }

    /**
     * Appends data to the stream.
     */
    public function append(string $data): void
    {
        $data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

        if ($data === [] || $data === false) {
            return;
        }

        array_push($this->data, ...$data);
    }

    /**
     * Appends data to the stream.
     */
    public function prepend(string $data): void
    {
        $data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

        if ($data === [] || $data === false) {
            return;
        }

        array_unshift($this->data, ...$data);
    }

    /**
     * Returns $count characters starting from the next character.
     */
    public function get(int $count = 1): string
    {
        $this->currentChar = $this->nextChar;

        if (!isset($this->data[$this->currentChar])) {
            ++$this->nextChar;

            return '';
        }

        if ($count === 1) {
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
     */
    public function peek(int $count = 1): string
    {
        if (!isset($this->data[$this->nextChar])) {
            return '';
        }

        if ($count === 1) {
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
     */
    public function isEoS(): bool
    {
        return !isset($this->data[$this->currentChar]);
    }

    /**
     * Returns the number of characters in the stream.
     */
    public function length(): int
    {
        return count($this->data);
    }

    /**
     * Empties the stream.
     */
    public function discard(): void
    {
        $this->data = [];
    }

    /**
     * Seeks to the given position in the stream.
     */
    public function seek(int $count): void
    {
        $this->nextChar += $count;
        $this->currentChar = $this->nextChar;
    }

    /**
     * Rewinds the steam to the beginning.
     */
    public function rewind(): void
    {
        $this->currentChar = 0;
    }
}
