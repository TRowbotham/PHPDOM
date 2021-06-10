<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\editing\editing_0\contenteditable;

use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/editing/editing-0/contenteditable/contenteditable-enumerated-ascii-case-insensitive.html
 */
class Contenteditable_enumerated_ascii_insensitiveTest extends TestCase
{
    use WindowTrait;

    public function testTranslate(): void
    {
        // $div = self::getWindow()->document->querySelectorAll('div');
        $div = self::getWindow()->document->getElementsByTagName('div');

        self::assertSame('false', $div[0]->contentEditable);
        self::assertSame('false', $div[1]->contentEditable);
        self::assertSame('inherit', $div[2]->contentEditable);

        $this->expectException(SyntaxError::class);
        $div[3]->contentEditable = "falſe";
    }

    public static function getDocumentName(): string
    {
        return 'contenteditable-enumerated-ascii-case-insensitive.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
