<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\text_level_semantics\the_time_element;

use Rowbot\DOM\Element\HTML\HTMLTimeElement;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function is_string;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/text-level-semantics/the-time-element/001.html
 */
class Time001Test extends TestCase
{
    use WindowTrait;

    public function testTimeElement(): void
    {
        $document = self::getWindow()->document;
        $timep = $document->getElementById('time');
        $times = $timep->getElementsByTagName('time');

        self::assertSame(4, $times->length);

        self::assertInstanceOf(HTMLTimeElement::class, $this->makeTime());
        self::assertInstanceOf(HTMLTimeElement::class, $times[0]);
    }

    public function testDatetimeAttributeShouldBeReflectedByTheDateTimeProperty(): void
    {
        self::assertSame(
            '2000-02-01T03:04:05Z',
            $this->makeTime('2000-02-01T03:04:05Z', '2001-02-01T03:04:05Z')->dateTime
        );
    }

    public function testDateTimeIDLPropertyShouldDefaultToAnEmptyString(): void
    {
        self::assertTrue(is_string($this->makeTime()->dateTime));
        self::assertSame('', $this->makeTime()->dateTime);
    }

    public function testDateTimePropertyShouldBeReadAndWrite(): void
    {
        self::assertSame(
            '2000-02-01T03:04:05Z',
            $this->makeTime(false, false, '2000-02-01T03:04:05Z')->dateTime
        );
    }

    public function testDatetimeAttributeShouldBeReflectedByTheDateTimePropertyEvenIfItIsInvalid(): void
    {
        self::assertSame('go fish', $this->makeTime('go fish')->dateTime);
    }

    public function testDatetimeAttributeShouldNotReflectTheTextContent(): void
    {
        self::assertSame('', $this->makeTime(false, '2000-02-01T03:04:05Z')->dateTime);
    }

    private function makeTime($dateTime = false, $contents = false, $dateTimeProp = false): HTMLTimeElement
    {
        $timeEl = self::getWindow()->document->createElement('time');

        if ($dateTime) {
            $timeEl->setAttribute('datetime', $dateTime);
        }

        if ($contents) {
            $timeEl->innerHTML = $contents;
        }

        if ($dateTimeProp) {
            $timeEl->dateTime = $dateTimeProp;
        }

        return $timeEl;
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__;
    }

    public static function getDocumentName(): string
    {
        return '001.html';
    }
}
