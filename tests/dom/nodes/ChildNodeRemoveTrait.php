<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Document;
use Rowbot\DOM\Node;

use function iterator_to_array;
use function method_exists;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/ChildNode-remove.js
 */
trait ChildNodeRemoveTrait
{
    abstract public function childNodeRemoveNodesProvider(): iterable;

    /**
     * @dataProvider childNodeRemoveNodesProvider
     */
    public function testRemove(Document $document, Node $node, Node $parent): void
    {
        $this->assertTrue(method_exists($node, 'remove'));
        $this->assertNull($node->parentNode);
        $this->assertNull($node->remove());
        $this->assertNull($node->parentNode);

        $this->assertNull($node->parentNode);
        $parent->appendChild($node);
        $this->assertSame($parent, $node->parentNode);
        $this->assertNull($node->remove());
        $this->assertNull($node->parentNode);
        $this->assertCount(0, $node->childNodes);

        $this->assertNull($node->parentNode);
        $before = $parent->appendChild($document->createComment('before'));
        $parent->appendChild($node);
        $after = $parent->appendChild($document->createComment('after'));
        $this->assertSame($parent, $node->parentNode);
        $this->assertNull($node->remove());
        $this->assertNull($node->parentNode);
        $this->assertSame([$before, $after], iterator_to_array($parent->childNodes));
    }
}
