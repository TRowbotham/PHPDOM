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
     * @param Node $node The node to check.
     *
     * @return int Returns one of NodeFilter's FILTER_* constants.
     *     - NodeFilter::FILTER_ACCEPT
     *     - NodeFilter::FILTER_REJECT
     *     - NodeFilter::FILTER_SKIP
     */
    private function filterNode($node)
    {
        // Let n be node’s nodeType attribute value minus 1.
        $n = $node->nodeType - 1;

        // If the nth bit (where 0 is the least significant bit) of whatToShow
        // is not set, return FILTER_SKIP.
        if (!((1 << $n) & $this->whatToShow)) {
            return NodeFilter::FILTER_SKIP;
        }

        // If filter is null, return FILTER_ACCEPT.
        if (!$this->filter) {
            return NodeFilter::FILTER_ACCEPT;
        }

        if ($this->filter instanceof NodeFilter) {
            return $this->filter->acceptNode($node);
        }

        return call_user_func($this->filter, $node);
    }
}
