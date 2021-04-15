<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-childElement-null.html
 */
class ElementChildElementNullHtmlTest extends TestCase
{
    public function testElementChildElement(): void
    {
        $document = new HTMLDocument();
        $parentEl = $document->createElement('parentEl');

        $this->assertNull($parentEl->firstElementChild);
        $this->assertNull($parentEl->lastElementChild);
    }
}
