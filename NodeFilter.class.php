<?php
namespace phpjs;

abstract class NodeFilter {
    const FILTER_ACCEPT = 1;
    const FILTER_REJECT = 2;
    const FILTER_SKIP = 3;

    const SHOW_ALL = 0xFFFFFFFF;
    const SHOW_ELEMENT = 0x1;
    const SHOW_ATTRIBUTE = 0x2;
    const SHOW_TEXT = 0x4;
    const SHOW_CDATA_SECTION = 0x8;
    const SHOW_ENTITY_REFERENCE = 0x10;
    const SHOW_ENTITY = 0x20;
    const SHOW_PROCESSING_INSTRUCTION = 0x40;
    const SHOW_COMMENT = 0x80;
    const SHOW_DOCUMENT = 0x100;
    const SHOW_DOCUMENT_TYPE = 0x200;
    const SHOW_DOCUMENT_FRAGMENT = 0x400;
    const SHOW_NOTATION = 0x800;

    final public function acceptNode(Node $aNode) {}

    /**
     * Filters a node.
     * @param  Node                    $aNode           The node to check.
     * @param  NodeIterator|TreeWalker $aNodeTraverser  An iterator to traverse the DOM tree.
     * @return int                                      Can be one of FILTER_ACCEPT | FILTER_REJECT |
     *                                                  FILTER_SKIP
     */
    final public static function _filter($aNode, $aNodeTraverser) {
        $n = $aNode->nodeType - 1;

        if (!((1 << $n) & $aNodeTraverser->whatToShow)) {
            return NodeFilter::FILTER_SKIP;
        }

        if (!$aNodeTraverser->filter) {
            return NodeFilter::FILTER_ACCEPT;
        }

        return call_user_func($aNodeTraverser->filter, $aNode);
    }
}
