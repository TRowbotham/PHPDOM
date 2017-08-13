<?php
namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Support\Stack;
use SeekableIterator;

abstract class ElementStack extends Stack implements SeekableIterator
{
    public function __construct()
    {
        parent::__construct();
    }

    public function insertAfter($item, $newItem)
    {
        $hash = $this->hash($item);

        if (!isset($this->map[$hash])) {
            throw new ParserException('The reference item does not exist in the stack.');
            return;
        }

        $newHash = $this->hash($newItem);

        if ($this->keys[$this->length - 1] === $hash) {
            parent::append($newItem);
            return;
        }

        $offset = array_flip($this->keys)[$hash];
        $this->map = array_slice($this->map, 0, $offset, true)
            + [$newHash => $newItem]
            + array_slice($this->map, $offset, null, true);
        array_splice($this->keys, $offset, 0, $newHash);
        $this->length++;
    }

    public function replace($item, $newItem)
    {
        if (!isset($this->map[$this->hash($item)])) {
            throw new ParserException('The reference item is not in the stack.');
            return;
        }

        parent::replace($item, $newItem);
    }

    public function seek($item)
    {
        $hash = $this->hash($item);

        if (!isset($this->map[$hash])) {
            throw new ParserException('Can\'t seek to this position.');
            return;
        }

        $this->position = array_flip($this->keys)[$hash];
    }
}
