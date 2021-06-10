<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\DOMStringMap;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/dataset.html
 */
class DatasetTest extends TestCase
{
    public function testHTMLElementsShouldHaveDataset(): HTMLElement
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $div = $document->createElement('div');

        self::assertInstanceOf(DOMStringMap::class, $div->dataset);

        return $div;
    }

    /**
     * @depends testHTMLElementsShouldHaveDataset
     */
    public function testShouldReturnUndefinedBeforeSettingAnAttribute(HTMLElement $div): HTMLElement
    {
        self::assertFalse(isset($div->dataset->foo));
        self::assertNull($div->dataset->foo);

        return $div;
    }

    /**
     * @depends testShouldReturnUndefinedBeforeSettingAnAttribute
     */
    public function testShouldReturnCorrectValue(HTMLElement $div): HTMLElement
    {
        $div->setAttribute('data-foo', 'value');
        self::assertTrue(isset($div->dataset->foo));
        self::assertSame('value', $div->dataset->foo);

        return $div;
    }

    /**
     * @depends testShouldReturnCorrectValue
     */
    public function testShouldReturnEmptyIfSetToEmptyString(HTMLElement $div): HTMLElement
    {
        $div->setAttribute('data-foo', '');
        self::assertTrue(isset($div->dataset->foo));
        self::assertSame('', $div->dataset->foo);

        return $div;
    }

    /**
     * @depends testShouldReturnEmptyIfSetToEmptyString
     */
    public function testShouldReturnUndefinedAfterRemovingAttribute(HTMLElement $div)
    {
        $div->removeAttribute('data-foo');
        self::assertFalse(isset($div->dataset->foo));
        self::assertNull($div->dataset->foo);
    }

    public function testRandomElementsShouldNotHaveDataset(): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $el = $document->createElementNS('test', 'test');

        self::assertNotInstanceOf(DOMStringMap::class, $el->dataset);
        self::assertNull($el->dataset);
    }

    public function testSVGElementsShouldHaveDataset(): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $el = $document->createElementNS("http://www.w3.org/2000/svg", "svg");

        self::assertInstanceOf(DOMStringMap::class, $el->dataset);
    }
}
