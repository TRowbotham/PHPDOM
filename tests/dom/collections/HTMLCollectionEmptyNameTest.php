<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\collections;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/collections/HTMLCollection-empty-name.html
 */
class HTMLCollectionEmptyNameTest extends TestCase
{
    use WindowTrait;

    public function testEmptyStringAsANameForDocumentGetElementsByTagName(): void
    {
        $c = self::getWindow()->document->getElementsByTagName('*');
        self::assertNull($c['']);
        self::assertNull($c->namedItem(''));
    }

    public function testEmptyStringAsANameForElementGetElementsByTagName(): void
    {
        $div = self::getWindow()->document->getElementById('test');
        $c = $div->getElementsByTagName('*');
        self::assertNull($c['']);
        self::assertNull($c->namedItem(''));
    }

    public function testEmptyStringAsANameForDocumentGetElementsByTagNameNS(): void
    {
        $c = self::getWindow()->document->getElementsByTagName("http://www.w3.org/1999/xhtml", "a");
        self::assertNull($c['']);
        self::assertNull($c->namedItem(''));
    }

    public function testEmptyStringAsANameForElementGetElementsByTagNameNS(): void
    {
        $div = self::getWindow()->document->getElementById('test');
        $c = $div->getElementsByTagName("http://www.w3.org/1999/xhtml", "a");
        self::assertNull($c['']);
        self::assertNull($c->namedItem(''));
    }

    public function testEmptyStringAsANameForDocumentGetElementsByClassName(): void
    {
        $c = self::getWindow()->document->getElementsByClassName('a');
        self::assertNull($c['']);
        self::assertNull($c->namedItem(''));
    }

    public function testEmptyStringAsANameForElementGetElementsByClassName(): void
    {
        $div = self::getWindow()->document->getElementById('test');
        $c = $div->getElementsByClassName('a');
        self::assertNull($c['']);
        self::assertNull($c->namedItem(''));
    }

    public function testEmptyStringAsANameForElementChildren(): void
    {
        $div = self::getWindow()->document->getElementById('test');
        $c = $div->children;
        self::assertNull($c['']);
        self::assertNull($c->namedItem(''));
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'HTMLCollection-empty-name.html';
    }
}
