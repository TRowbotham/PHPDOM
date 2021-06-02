<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\DOM\HTMLDocument;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/document-metadata/the-base-element/base_href_invalid.html
 */
class Base_href_invalidTest extends TestCase
{
    public function testUnparsableHrefShouldReturnAttrValue(): void
    {
        $document = new HTMLDocument();
        $b = $document->createElement('base');
        $b->setAttribute('href', '//test:test');
        self::assertSame('//test:test', $b->href);
    }
}
