<?php
namespace Rowbot\DOM;

use Exception;
use Rowbot\DOM\Exception\InvalidStateError;

use function call_user_func;

trait NodeFilterUtils
{
    /**
     * @var bool
     */
    private $isActive = false;

    /**
     * Filters a node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-filter
     *
     * @param \Rowbot\DOM\Node $node The node to check.
     *
     * @return int Returns one of NodeFilter's FILTER_* constants.
     *     - NodeFilter::FILTER_ACCEPT
     *     - NodeFilter::FILTER_REJECT
     *     - NodeFilter::FILTER_SKIP
     *
     * @throws \Rowbot\DOM\Exception\InvalidStateError
     */
    private function filterNode(Node $node): int
    {
        if ($this->isActive) {
            throw new InvalidStateError();
        }

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

        $this->isActive = true;


        try {
            // Let $result be the return value of call a user object's operation
            // with traverser's filter, "acceptNode", and Node. If this throws
            // an exception, then unset traverser's active flag and rethrow the
            // exception.
            if ($this->filter instanceof NodeFilter) {
                $result = $this->filter->acceptNode($node);
            } else {
                $result = call_user_func($this->filter, $node);
            }
        } catch (Exception $e) {
            $this->isActive = false;
            throw $e;
        }

        $this->isActive = false;

        return $result;
    }
}
