<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-insert-ext
 */
interface NodeInsertHook
{
    public function onInsert(Node $insertedNode): void;
}
