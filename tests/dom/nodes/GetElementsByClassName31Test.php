<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-31.htm
 */
class GetElementsByClassName31Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        self::markTestSkipped('We don\'t support iframes yet');

        $document = self::getWindow()->document;
        $iframe = $document->createElement('iframe');
        $iframe->onload = static function () use ($iframe): void {
            $collection = $iframe->getElementsByClassName("foo");
            self::assertSame(3, $collection->length);
            self::assertSame('html', $collection[0]->parentNode->localName);
            self::assertSame('head', $collection[1]->parentNode->localName);
            self::assertSame('body', $collection[2]->parentNode->localName);
        };
        $iframe->src = self::getHtmlBaseDir() . DS . 'getElementsByClassNameFrame.html';
        $document->body->appendChild($iframe);
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-31.html';
    }
}
