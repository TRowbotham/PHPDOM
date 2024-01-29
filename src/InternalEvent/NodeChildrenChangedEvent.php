<?php

declare(strict_types=1);

namespace Rowbot\DOM\InternalEvent;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-children-changed-ext
 */
class NodeChildrenChangedEvent extends Event
{
}
