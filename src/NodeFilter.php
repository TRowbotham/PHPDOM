<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * An object implementing NodeFilter can be used as a filter callback that can
 * further refine the results beyond what the whatToShow bitmask can filter.
 *
 * @see https://dom.spec.whatwg.org/#interface-nodefilter
 */
interface NodeFilter
{
    public const FILTER_ACCEPT = 1;
    public const FILTER_REJECT = 2;
    public const FILTER_SKIP   = 3;

    public const SHOW_ALL                    = 0xFFFFFFFF;
    public const SHOW_ELEMENT                = 0x1;
    public const SHOW_ATTRIBUTE              = 0x2;
    public const SHOW_TEXT                   = 0x4;
    public const SHOW_CDATA_SECTION          = 0x8;
    public const SHOW_ENTITY_REFERENCE       = 0x10;
    public const SHOW_ENTITY                 = 0x20;
    public const SHOW_PROCESSING_INSTRUCTION = 0x40;
    public const SHOW_COMMENT                = 0x80;
    public const SHOW_DOCUMENT               = 0x100;
    public const SHOW_DOCUMENT_TYPE          = 0x200;
    public const SHOW_DOCUMENT_FRAGMENT      = 0x400;
    public const SHOW_NOTATION               = 0x800;

    /**
     * A method used to filter the nodes returned by NodeIterator and TreeWalker
     * objects.
     *
     * @see https://dom.spec.whatwg.org/#dom-nodefilter-acceptnode
     *
     * @param \Rowbot\DOM\Node $node The current node being processed.
     *
     * @return int Returns one of NodeFilter's FILTER_* constants.
     *                 - NodeFilter::FILTER_ACCEPT
     *                 - NodeFilter::FILTER_REJECT
     *                 - NodeFilter::FILTER_SKIP
     */
    public function acceptNode(Node $node): int;
}
