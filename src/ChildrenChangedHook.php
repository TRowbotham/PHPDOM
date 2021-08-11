<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#concept-node-children-changed-ext
 */
interface ChildrenChangedHook
{
    public function onChildrenChanged(): void;
}
