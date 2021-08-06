<?php

declare(strict_types=1);

namespace Rowbot\DOM;

final class RangeBoundary
{
    public Node $startNode;

    public int $startOffset;

    public Node $endNode;

    public int $endOffset;

    public function __construct(Node $startNode, int $startOffset, Node $endNode, int $endOffset)
    {
        $this->startNode = $startNode;
        $this->startOffset = $startOffset;
        $this->endNode = $endNode;
        $this->endOffset = $endOffset;
    }
}
