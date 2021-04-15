<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Text;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createTextNode.html
 */
class DocumentCreateTextNodeTest extends CharacterDataTestCase
{
    /**
     * @dataProvider textNodeDataProvider
     */
    public function testCreateTextNode(
        string $method,
        string $iface,
        int $nodeType,
        string $nodeValue,
        $value
    ): void {
        $this->checkDocumentCreateMethod(new HTMLDocument(), $method, $iface, $nodeType, $nodeValue, $value);
    }

    public function textNodeDataProvider(): Generator
    {
        foreach ($this->valuesProvider() as $value) {
            yield ['createTextNode', Text::class, 3, '#text', $value];
        }
    }
}
