<?php
namespace Rowbot\DOM\Support;

class CodePointStream
{
    const SEEK_RELATIVE = 1;
    const SEEK_ABSOLUTE = 2;

    private $data;
    private $currentChar;
    private $nextChar;

    public function __construct($data = '')
    {
        $this->currentChar = 0;
        $this->nextChar = 0;

        if ($data) {
            $this->data = \preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);
            return;
        }

        $this->data = [];
    }

    public function append($data)
    {
        $data = \preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($data)) {
            return;
        }

        \array_push($this->data, ...$data);
    }

    public function prepend($data)
    {
        $data = \preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($data)) {
            return;
        }

        \array_unshift($this->data, ...$data);
    }

    public function get($count = 1)
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

    public function peek($count = 1)
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

    public function isEoS()
    {
        return !isset($this->data[$this->currentChar]);
    }

    public function length()
    {
        return \count($this->data);
    }

    public function discard()
    {
        $this->data = [];
    }

    public function seek($count)
    {
        $this->nextChar += $count;
        $this->currentChar = $this->nextChar;
    }

    public function rewind()
    {
        $this->currentChar = 0;
    }
}
