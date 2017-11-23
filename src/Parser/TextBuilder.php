<?php
namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Node;

class TextBuilder
{
    private $node;
    private $data;

    public function __construct()
    {
        $this->data = '';
    }

    public function append(string $text)
    {
        $this->data .= $text;
    }

    public function setNode(Node $node)
    {
        $this->node = $node;
    }

    public function flushText()
    {
        if (!$this->node) {
            return;
        }

        $this->node->data = $this->data;
        $this->data = '';
        $this->node = null;
    }
}
