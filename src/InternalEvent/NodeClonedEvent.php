<?php

declare(strict_types=1);

namespace Rowbot\DOM\InternalEvent;

use Rowbot\DOM\Document;
use Rowbot\DOM\Node;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-clone-ext
 */
class NodeClonedEvent extends Event
{
    public readonly Node $copy;

    public readonly Node $node;

    public readonly Document $document;

    public readonly bool $cloneChildren;

    public function __construct(Node $copy, Node $node, Document $document, bool $cloneChildren)
    {
        $this->copy = $copy;
        $this->node = $node;
        $this->document = $document;
        $this->cloneChildren = $cloneChildren;
    }
}
