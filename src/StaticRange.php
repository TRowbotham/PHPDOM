<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Range\BoundaryPoint;
use TypeError;

use function is_int;

/**
 * @see https://dom.spec.whatwg.org/#staticrange
 *
 * @phpstan-type StaticRangeInit array{startContainer: \Rowbot\DOM\Node, startOffset: int<0, max>, endContainer: \Rowbot\DOM\Node, endOffset: int<0, max>}
 */
final class StaticRange extends AbstractRange
{
    /**
     * @param StaticRangeInit $init
     */
    public function __construct(array $init)
    {
        $this->validateInit($init);

        // 1. If init["startContainer"] or init["endContainer"] is a DocumentType or Attr node, then throw an "InvalidNodeTypeError" DOMException.
        foreach (['startContainer', 'endContainer'] as $container) {
            if ($init[$container] instanceof DocumentType || $init[$container] instanceof Attr) {
                throw new InvalidNodeTypeError();
            }
        }

        // 2. Set this’s start to (init["startContainer"], init["startOffset"]) and end to (init["endContainer"], init["endOffset"]).
        parent::__construct(
            new BoundaryPoint($init['startContainer'], $init['startOffset']),
            new BoundaryPoint($init['endContainer'], $init['endOffset']),
        );
    }

    /**
     * @phpstan-assert StaticRangeInit $init
     */
    private function validateInit(array $init): void
    {
        foreach (['startContainer', 'endContainer'] as $container) {
            if (!isset($init[$container]) || !$init[$container] instanceof Node) {
                throw new TypeError();
            }
        }

        foreach (['startOffset', 'endOffset'] as $offset) {
            if (!isset($init[$offset]) || !is_int($init[$offset]) || $init[$offset] < 0) {
                throw new TypeError();
            }
        }
    }
}
