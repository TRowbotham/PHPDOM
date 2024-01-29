<?php

declare(strict_types=1);

namespace Rowbot\DOM\InternalEvent;

use Rowbot\DOM\Node;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-remove-ext
 */
class NodeRemovedEvent extends Event
{
    public readonly Node $removedNode;

    public readonly ?Node $oldParent;

    public function __construct(Node $removedNode, ?Node $oldParent)
    {
        $this->removedNode = $removedNode;
        $this->oldParent = $oldParent;
    }
}
