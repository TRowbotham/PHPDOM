<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser;

use Rowbot\DOM\CharacterData;

class TextBuilder
{
    /**
     * @var \Rowbot\DOM\CharacterData|null
     */
    private $node;

    /**
     * @var string
     */
    private $data;

    public function __construct()
    {
        $this->data = '';
    }

    /**
     * Appends string to the text builder.
     */
    public function append(string $text): void
    {
        $this->data .= $text;
    }

    public function getNode(): ?CharacterData
    {
        return $this->node;
    }

    /**
     * Sets the text node that the text builder is operating on.
     */
    public function setNode(CharacterData $node): void
    {
        $this->node = $node;
    }

    /**
     * Adds the accumulated string data to the node.
     */
    public function flushText(): void
    {
        if ($this->node === null) {
            return;
        }

        $this->node->data .= $this->data;
        $this->data = '';
        $this->node = null;
    }
}
