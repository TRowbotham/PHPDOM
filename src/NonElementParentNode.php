<?php
namespace Rowbot\DOM;

trait NonElementParentNode
{
    /**
     * Returns the first element in tree order whose id attribute is equal to
     * $elementId or null if no element is found.
     *
     * @param string $elementId The id of the element you are trying to find.
     *
     * @return Element|null
     */
    public function getElementById($elementId)
    {
        if (!is_string($elementId)) {
            return null;
        }

        $tw = new TreeWalker(
            $this,
            NodeFilter::SHOW_ELEMENT,
            function ($node) use ($elementId) {
                if (mb_strtolower($node->id) === mb_strtolower($elementId)) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            }
        );

        return $tw->nextNode();
    }
}
