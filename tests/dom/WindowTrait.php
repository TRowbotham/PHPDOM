<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom;

use Closure;
use Rowbot\DOM\DocumentBuilder;

use function array_pop;
use function file_get_contents;
use function method_exists;

use const DIRECTORY_SEPARATOR as DS;

trait WindowTrait
{
    /**
     * @var list<\Closure>
     */
    private static $cleanupStack = [];

    /**
     * @var \Rowbot\DOM\Tests\dom\Window
     */
    private static $window;

    abstract public static function getDocumentName(): string;

    abstract public static function getHtmlBaseDir(): string;

    public static function tearDownAfterClass(): void
    {
        while (isset(self::$cleanupStack[0])) {
            array_pop(self::$cleanupStack)();
        }

        self::$window = null;
    }

    public static function registerCleanup(Closure $callback): void
    {
        self::$cleanupStack[] = $callback;
    }

    public static function getWindow(): Window
    {
        if (self::$window) {
            return self::$window;
        }

        $builder = DocumentBuilder::create();
        $builder->setContentType('text/html');

        if (method_exists(__CLASS__, 'customizeDocument')) {
            self::customizeDocument($builder);
        }

        $document = $builder->createFromString(file_get_contents(self::getHtmlBaseDir() . DS . self::getDocumentName()));
        self::$window = new Window($document);

        return self::$window;
    }
}
