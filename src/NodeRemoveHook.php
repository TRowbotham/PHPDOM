<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-remove-ext
 */
interface NodeRemoveHook
{
    public function onRemove(Node $removedNode, ?Node $parent = null): void;
}
