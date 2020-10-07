<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

/**
 * @see https://dom.spec.whatwg.org/#interface-nonelementparentnode
 */
interface NonElementParentNode
{
    /**
     * Returns the first element within node's descendants whose ID is elementId.
     *
     * @see https://dom.spec.whatwg.org/#dom-nonelementparentnode-getelementbyid
     */
    public function getElementById(string $elementId): ?Element;
}
