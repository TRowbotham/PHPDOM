<?php

declare(strict_types=1);

namespace Rowbot\DOM;

final class RangeBoundary
{
    /**
     * @var \Rowbot\DOM\Node
     */
    public $startNode;

    /**
     * @var int
     */
    public $startOffset;

    /**
     * @var \Rowbot\DOM\Node
     */
    public $endNode;

    /**
     * @var int
     */
    public $endOffset;

    public function __construct(Node $startNode, int $startOffset, Node $endNode, int $endOffset)
    {
        $this->startNode = $startNode;
        $this->startOffset = $startOffset;
        $this->endNode = $endNode;
        $this->endOffset = $endOffset;
    }
}
