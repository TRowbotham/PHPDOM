<?php
namespace Rowbot\DOM;

/**
 * An object implementing NodeFilter can be used as a filter callback that can
 * further refine the results beyond what the whatToShow bitmask can filter.
 *
 * @see https://dom.spec.whatwg.org/#interface-nodefilter
 */
interface NodeFilter
{
    const FILTER_ACCEPT = 1;
    const FILTER_REJECT = 2;
    const FILTER_SKIP   = 3;

    const SHOW_ALL                    = 0xFFFFFFFF;
    const SHOW_ELEMENT                = 0x1;
    const SHOW_ATTRIBUTE              = 0x2;
    const SHOW_TEXT                   = 0x4;
    const SHOW_CDATA_SECTION          = 0x8;
    const SHOW_ENTITY_REFERENCE       = 0x10;
    const SHOW_ENTITY                 = 0x20;
    const SHOW_PROCESSING_INSTRUCTION = 0x40;
    const SHOW_COMMENT                = 0x80;
    const SHOW_DOCUMENT               = 0x100;
    const SHOW_DOCUMENT_TYPE          = 0x200;
    const SHOW_DOCUMENT_FRAGMENT      = 0x400;
    const SHOW_NOTATION               = 0x800;

    /**
     * A method used to filter the nodes returned by NodeIterator and TreeWalker
     * objects.
     *
     * @see https://dom.spec.whatwg.org/#dom-nodefilter-acceptnode
     *
     * @param  Node   $node The current node being processed.
     *
     * @return int Returns one of NodeFilter's FILTER_* constants.
     *     - NodeFilter::FILTER_ACCEPT
     *     - NodeFilter::FILTER_REJECT
     *     - NodeFilter::FILTER_SKIP
     */
    public function acceptNode(Node $node);
}
