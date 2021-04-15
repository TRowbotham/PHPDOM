<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\traversal;

use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\Tests\dom\Constants;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/NodeFilter-constants.html
 */
class NodeFilterConstantsTest extends TestCase
{
    use Constants;

    public function constantsProvider(): array
    {
        return [
            [
                [
                    [NodeFilter::class, 'NodeFilter interface object'],
                ],
                [
                    // "acceptNode"
                    ['FILTER_ACCEPT', 1],
                    ['FILTER_REJECT', 2],
                    ['FILTER_SKIP',   3],

                    // "whatToShow"
                    ['SHOW_ALL',              0xFFFFFFFF],
                    ['SHOW_ELEMENT',                 0x1],
                    ['SHOW_ATTRIBUTE',               0x2],
                    ['SHOW_TEXT',                    0x4],
                    ['SHOW_CDATA_SECTION',           0x8],
                    ['SHOW_ENTITY_REFERENCE',       0x10],
                    ['SHOW_ENTITY',                 0x20],
                    ['SHOW_PROCESSING_INSTRUCTION', 0x40],
                    ['SHOW_COMMENT',                0x80],
                    ['SHOW_DOCUMENT',              0x100],
                    ['SHOW_DOCUMENT_TYPE',         0x200],
                    ['SHOW_DOCUMENT_FRAGMENT',     0x400],
                    ['SHOW_NOTATION',              0x800],
                ],
            ],
        ];
    }
}
