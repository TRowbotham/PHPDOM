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
                return strcasecmp($node->id, $elementId) == 0 ?
                    NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
            }
        );

        return $tw->nextNode();
    }
}
