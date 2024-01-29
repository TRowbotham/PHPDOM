<?php

declare(strict_types=1);

namespace Rowbot\DOM\InternalEvent;

use Rowbot\DOM\Node;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-adopt-ext
 */
class NodeAdoptedEvent extends Event
{
    public readonly Node $node;

    public readonly ?Node $oldDocument;

    public function __construct(Node $node, ?Node $oldDocument)
    {
        $this->node = $node;
        $this->oldDocument = $oldDocument;
    }
}
