<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

trait NonElementParentNodeTrait
{
    /**
     * @see @see https://dom.spec.whatwg.org/#dom-nonelementparentnode-getelementbyid
     */
    public function getElementById(string $elementId): ?Element
    {
        if ($elementId === '') {
            return null;
        }

        $tw = new TreeWalker(
            $this,
            NodeFilter::SHOW_ELEMENT,
            static function (Node $node) use ($elementId): int {
                if ($node->id === $elementId) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            }
        );

        return $tw->nextNode();
    }
}
