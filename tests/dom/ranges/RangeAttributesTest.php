<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-attributes.html
 */
class RangeAttributesTest extends TestCase
{
    public function testAttributes(): void
    {
        $document = new HTMLDocument();
        $r = $document->createRange();

        $this->assertSame($document, $r->startContainer);
        $this->assertSame($document, $r->endContainer);
        $this->assertSame(0, $r->startOffset);
        $this->assertSame(0, $r->endOffset);
        $this->assertTrue($r->collapsed);

        $r->detach();

        $this->assertSame($document, $r->startContainer);
        $this->assertSame($document, $r->endContainer);
        $this->assertSame(0, $r->startOffset);
        $this->assertSame(0, $r->endOffset);
        $this->assertTrue($r->collapsed);
    }
}
