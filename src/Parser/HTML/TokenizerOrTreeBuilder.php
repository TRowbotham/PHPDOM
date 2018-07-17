<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Element\Element;

use function count;

trait TokenizerOrTreeBuilder
{
    use ParserCommon;

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#adjusted-current-node
     *
     * @return \Rowbot\DOM\Element\Element
     */
    public function getAdjustedCurrentNode(): Element
    {
        if ($this->isFragmentCase && count($this->openElements) == 1) {
            return $this->contextElement;
        }

        return $this->openElements->bottom();
    }
}
