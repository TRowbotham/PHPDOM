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

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->data = '';
    }

    /**
     * Appends string to the text builder.
     *
     * @param string $text
     *
     * @return void
     */
    public function append(string $text): void
    {
        $this->data .= $text;
    }

    /**
     * Sets the text node that the text builder is operating on.
     *
     * @param \Rowbot\DOM\CharacterData $node
     *
     * @return void
     */
    public function setNode(CharacterData $node): void
    {
        $this->node = $node;
    }

    /**
     * Adds the accumulated string data to the node.
     *
     * @return void
     */
    public function flushText(): void
    {
        if ($this->node === null) {
            return;
        }

        $this->node->data = $this->data;
        $this->data = '';
        $this->node = null;
    }
}
