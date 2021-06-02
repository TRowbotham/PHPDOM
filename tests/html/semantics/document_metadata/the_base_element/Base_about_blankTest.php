<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\document_metadata\the_base_element;

use Rowbot\DOM\Tests\TestCase;

use function preg_replace;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/document-metadata/the-base-element/base_about_blank.html
 */
class Base_about_blankTest extends TestCase
{
    public function testBase(): void
    {
        $this->markTestSkipped('We don\'t support iframes.');

        $doc = $frames[0]->document;
        $b = $doc->createElement('base');
        $b->setAttribute('href', 'test');
        $newBaseValue = preg_replace('/\\/[^\\/]*$/', "/", $location->href) . 'test';
        self::assertSame($newBaseValue, $b->href);
        self::assertSame($location->href, $doc->baseURI);
        $doc->head->appendChild($b);
        self::assertSame($newBaseValue, $doc->baseURI);
    }
}
