<?php
namespace Rowbot\DOM\Parser\HTML;

trait TokenizerOrTreeBuilder
{
    use ParserCommon;

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#adjusted-current-node
     *
     * @return Element
     */
    public function getAdjustedCurrentNode()
    {
        if ($this->isFragmentCase && count($this->openElements) == 1) {
            return $this->contextElement;
        }

        return $this->openElements->bottom();
    }
}
