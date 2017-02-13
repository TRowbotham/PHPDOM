<?php
namespace phpjs;

use phpjs\elements\Element;
use phpjs\elements\HTMLSlotElement;

/**
 * @see https://dom.spec.whatwg.org/#mixin-slotable
 */
trait Slotable
{
    /**
     * @see https://dom.spec.whatwg.org/#find-a-slot
     * @param  [type] $aSlotable [description]
     * @param  [type] $aOpen     [description]
     * @return [type]            [description]
     */
    protected function findSlot($aSlotable, $aOpen = null)
    {
        if ($aSlotable->mParentNode === null) {
            return null;
        }

        $shadow = null;

        if ($aSlotable->mParentNode instanceof Element) {
            $shadow = $aSlotable->mParentNode->shadowRoot;
        }

        if ($shadow === null) {
            return null;
        }

        if ($aOpen && $shadow->getMode() !== ShadowRootMode::OPEN) {
            return null;
        }

        $name = $aSlotable->name;
        $tw = new TreeWalker(
            $shadow,
            NodeFilter::SHOW_ELEMENT,
            function ($aNode) use ($name) {
                if ($aNode instanceof HTMLSlotElement &&
                    $aNode->name === $name
                ) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            }
        );

        return $tw->nextNode();
    }

    /**
     * @see https://dom.spec.whatwg.org/#find-slotables
     *
     * @param  [type] $aSlot [description]
     * @return [type]        [description]
     */
    public function findSlotables($aSlot)
    {
        $result = [];
        $root = $aSlot->getRootNode();

        if (!($root instanceof ShadowRoot)) {
            return $result;
        }

        $host = $root->getHost();
        $tw = new TreeWalker(
            $host,
            NodeFilter::SHOW_ELEMENT | NodeFilter::SHOW_TEXT
        );

        while (($slotable = $tw->nextNode())) {
            $foundSlot = $this->findSlot($aSlotable);

            if ($foundSlot instanceof HTMLSlotElement) {
                $result[] = $aSlotable;
            }
        }

        return $result;
    }
}
