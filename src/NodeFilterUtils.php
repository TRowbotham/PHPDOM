<?php
namespace Rowbot\DOM;

trait NodeFilterUtils
{
    /**
     * Filters a node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-filter
     *
     * @param Node $aNode The node to check.
     *
     * @return int Returns one of NodeFilter's FILTER_* constants.
     *     - NodeFilter::FILTER_ACCEPT
     *     - NodeFilter::FILTER_REJECT
     *     - NodeFilter::FILTER_SKIP
     */
    private function filterNode($aNode)
    {
        // Let n be node’s nodeType attribute value minus 1.
        $n = $aNode->nodeType - 1;

        // If the nth bit (where 0 is the least significant bit) of whatToShow
        // is not set, return FILTER_SKIP.
        if (!((1 << $n) & $this->mWhatToShow)) {
            return NodeFilter::FILTER_SKIP;
        }

        // If filter is null, return FILTER_ACCEPT.
        if (!$this->mFilter) {
            return NodeFilter::FILTER_ACCEPT;
        }

        if ($this->mFilter instanceof NodeFilter) {
            return $this->mFilter->acceptNode($aNode);
        }

        return call_user_func($this->mFilter, $aNode);
    }
}
