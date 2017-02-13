<?php
namespace phpjs\support;

class CodePointStream
{
    const SEEK_RELATIVE = 1;
    const SEEK_ABSOLUTE = 2;

    private $data;
    private $position;

    public function __construct($data = '')
    {
        $this->position = 0;

        if ($data) {
            $this->data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);
            return;
        }

        $this->data = [];
    }

    public function append($data)
    {
        $data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($data)) {
            return;
        }

        array_push($this->data, ...$data);
    }

    public function prepend($data)
    {
        $data = preg_split('//u', $data, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($data)) {
            return;
        }

        array_unshift($this->data, ...$data);
    }

    public function get($count = 1)
    {
        if (!isset($this->data[$this->position])) {
            $this->position++;

            return '';
        }

        if ($count == 1) {
            return $this->data[$this->position++];
        }

        $str = '';

        while ($count--) {
            $str .= $this->data[$this->position++];

            if (!isset($this->data[$this->position])) {
                break;
            }
        }

        return $str;
    }

    public function peek($count = 1)
    {
        if (!isset($this->data[$this->position])) {
            return '';
        }

        if ($count == 1) {
            return $this->data[$this->position];
        }

        $position = $this->position;
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
        return !isset($this->data[max(0, $this->position - 1)]);
    }

    public function length()
    {
        return count($this->data);
    }

    public function discard()
    {
        $this->data = [];
    }

    public function seek($count)
    {
        $this->position += $count;
    }

    public function rewind()
    {
        $this->position = 0;
    }
}
