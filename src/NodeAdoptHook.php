<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-adopt-ext
 */
interface NodeAdoptHook
{
    public function onAdopt(Node $node, Document $oldDocument): void;
}
