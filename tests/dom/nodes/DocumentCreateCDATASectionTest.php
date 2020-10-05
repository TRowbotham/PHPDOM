<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createCDATASection.html
 */
class DocumentCreateCDATASectionTest extends TestCase
{
    public function testCreateCDATASection(): void
    {
        $this->expectException(NotSupportedError::class);

        $document = new HTMLDocument();
        $document->createCDATASection('foo');
    }
}
