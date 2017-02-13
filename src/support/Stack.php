<?php
namespace phpjs\support;

use Exception;

class Stack extends OrderedSet
{
    public function __construct()
    {
        parent::__construct();
    }

    public function push($item)
    {
        if (isset($this->map[$this->hash($item)])) {
            throw new Exception('Item already exists on the stack.');
            return;
        }

        parent::append($item);
    }

    public function pop()
    {
        if ($this->length == 0) {
            throw new Exception('Can\'t pop an item from an empty stack.');
            return;
        }

        array_pop($this->keys);
        $this->length--;

        return array_pop($this->map);
    }

    public function top()
    {
        if ($this->length == 0) {
            throw new Exception('Can\'t get the top item of an empty stack.');
            return;
        }

        return $this->map[$this->keys[$this->length - 1]];
    }

    public function next()
    {
        $this->position--;
    }

    public function prev()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = $this->length - 1;
    }

    public function valid()
    {
        return $this->position >= 0 && $this->position < $this->length;
    }
}
