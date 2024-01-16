<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Node;
use Rowbot\DOM\NodeInsertionLocation;

class AdjustedInsertionLocation
{
    public Node $node;

    public NodeInsertionLocation $location;

    public function __construct(Node $node, NodeInsertionLocation $location)
    {
        $this->node = $node;
        $this->location = $location;
    }
}
