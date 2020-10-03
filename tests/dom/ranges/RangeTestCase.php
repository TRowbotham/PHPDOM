<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

abstract class RangeTestCase extends TestCase
{
    use Common;

    protected static $runAutoSetup = false;
    protected static $document;

    abstract public static function fetchDocument(): string;

    public static function setUpBeforeClass(): void
    {
        static $hasRun = false;

        if ($hasRun) {
            return;
        }

        $hasRun = true;
        $parser = new DOMParser();
        self::$document = $parser->parseFromString(static::fetchDocument(), 'text/html');

        if (static::$runAutoSetup) {
            self::setupRangeTests(self::$document);
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$runAutoSetup = false;
    }
}
