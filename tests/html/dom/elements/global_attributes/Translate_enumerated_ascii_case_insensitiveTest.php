<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/translate-enumerated-ascii-case-insensitive.html
 */
class Translate_enumerated_ascii_case_insensitiveTest extends TestCase
{
    use WindowTrait;

    public function testTranslate(): void
    {
        // $span = self::getWindow()->document->querySelectorAll('span');
        $span = self::getWindow()->document->getElementsByTagName('span');

        self::assertTrue($span[0]->translate);
        self::assertTrue($span[1]->translate);
        self::assertFalse($span[2]->translate);
    }

    public static function getDocumentName(): string
    {
        return 'translate-enumerated-ascii-case-insensitive.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
