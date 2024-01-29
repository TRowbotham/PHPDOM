<?php

declare(strict_types=1);

namespace Rowbot\DOM\InternalEvent;

use Rowbot\DOM\Node;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-insert-ext
 */
class NodeInsertedEvent extends Event
{
    public readonly Node $insertedNode;

    public function __construct(Node $insertedNode)
    {
        $this->insertedNode = $insertedNode;
    }
}
