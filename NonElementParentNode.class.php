<?php
trait NonElementParentNode {
    /**
     * Returns the first element in tree order whose id attribute is equal to $aElementId or
     * null if no element is found.
     * @param  string       $aElementId The id of the element you are trying to find.
     * @return Element|null
     */
    public function getElementById($aElementId) {
        if (!is_string($aElementId)) {
            return null;
        }

        $tw = new TreeWalker($this, NodeFilter::SHOW_ELEMENT, function($aNode) use ($aElementId) {
            return $aNode->id == $aElementId ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
        });

        return $tw->nextNode();
    }
}
