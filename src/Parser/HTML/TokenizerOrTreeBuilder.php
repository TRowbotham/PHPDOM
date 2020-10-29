<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;

use function count;

trait TokenizerOrTreeBuilder
{
    use ParserCommon;

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#adjusted-current-node
     */
    public function getAdjustedCurrentNode(): ?Element
    {
        if ($this->isFragmentCase && count($this->openElements) === 1) {
            return $this->contextElement;
        }

        try {
            return $this->openElements->bottom();
        } catch (EmptyStackException $e) {
            return null;
        }
    }
}
