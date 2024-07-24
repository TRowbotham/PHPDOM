<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom;

use Rowbot\DOM\Document;
use Rowbot\DOM\HTMLDocument;

use function call_user_func;

trait DocumentGetter
{
    private static ?HTMLDocument $htmlDocument = null;

    private static ?Document $xmlDocument = null;

    public static function getHTMLDocument(?callable $callback = null): HTMLDocument
    {
        if (self::$htmlDocument === null) {
            self::$htmlDocument = (new HTMLDocument())
                ->implementation
                ->createHTMLDocument();

            if ($callback !== null) {
                call_user_func($callback, self::$htmlDocument);
            }
        }

        return self::$htmlDocument;
    }

    public function getXMLDocument(?callable $callback = null): Document
    {
        if (self::$xmlDocument === null) {
            self::$xmlDocument = new Document();

            if ($callback !== null) {
                call_user_func($callback, self::$htmlDocument);
            }
        }

        return self::$xmlDocument;
    }

    public function tearDown(): void
    {
        self::$htmlDocument = null;
        self::$xmlDocument = null;
    }
}
