<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-clone-ext
 */
interface NodeCloneHook
{
    public function onClone(Node $copy, Node $node, Document $document, bool $cloneChildren = false): void;
}
