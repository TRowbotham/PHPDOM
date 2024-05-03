<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Exception\InvalidStateError;
use Throwable;

abstract class NodeTraverser
{
    /**
     * @var \Rowbot\DOM\NodeFilter|callable|null
     */
    public readonly mixed $filter;

    /**
     * @var \Rowbot\DOM\NodeFilter::SHOW_*
     */
    public readonly int $whatToShow;

    private bool $isActive;

    /**
     * @param \Rowbot\DOM\NodeFilter::SHOW_* $whatToShow
     */
    public function __construct(int $whatToShow, callable|NodeFilter|null $filter)
    {
        $this->whatToShow = $whatToShow;
        $this->isActive = false;
        $this->filter = $filter;
    }

    /**
     * @see https://dom.spec.whatwg.org/#concept-node-filter
     *
     * @return \Rowbot\DOM\NodeFilter::FILTER_*
     *
     * @throws \Rowbot\DOM\Exception\InvalidStateError
     */
    protected function filterNode(Node $node): int
    {
        // 1. If traverser’s active flag is set, then throw an "InvalidStateError" DOMException.
        if ($this->isActive) {
            throw new InvalidStateError();
        }

        // 2. Let n be node’s nodeType attribute value − 1.
        $n = $node->nodeType - 1;

        // 3. If the nth bit (where 0 is the least significant bit) of traverser’s whatToShow is not set, then return
        // FILTER_SKIP.
        if (!((1 << $n) & $this->whatToShow)) {
            return NodeFilter::FILTER_SKIP;
        }

        // 4. If traverser’s filter is null, then return FILTER_ACCEPT.
        if ($this->filter === null) {
            return NodeFilter::FILTER_ACCEPT;
        }

        // 5. Set traverser’s active flag.
        $this->isActive = true;

        // 6. Let result be the return value of call a user object’s operation with traverser’s filter, "acceptNode",
        // and « node ». If this throws an exception, then unset traverser’s active flag and rethrow the exception.
        // 7. Unset traverser’s active flag.
        try {
            $result = $this->filter instanceof NodeFilter
                ? $this->filter->acceptNode($node)
                : ($this->filter)($node);
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $this->isActive = false;
        }

        // 8. Return result.
        return $result;
    }
}
