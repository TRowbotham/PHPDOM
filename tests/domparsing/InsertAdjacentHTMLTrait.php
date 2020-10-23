<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\NoModificationAllowedError;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/insert_adjacent_html.js
 */
trait InsertAdjacentHTMLTrait
{
    use WindowTrait;

    /**
     * @dataProvider elementProvider
     */
    public function testShouldThrowForBeforebeginAndAfterendText(Element $element): void
    {
        $this->assertThrows(static function () use ($element): void {
            $element->insertAdjacentHTML('afterend', '');
        }, NoModificationAllowedError::class);
        $this->assertThrows(static function () use ($element): void {
            $element->insertAdjacentHTML('beforebegin', '');
        }, NoModificationAllowedError::class);
        $this->assertThrows(static function () use ($element): void {
            $element->insertAdjacentHTML('afterend', 'foo');
        }, NoModificationAllowedError::class);
        $this->assertThrows(static function () use ($element): void {
            $element->insertAdjacentHTML('afterend', 'foo');
        }, NoModificationAllowedError::class);
    }

    /**
     * @dataProvider elementProvider
     */
    public function testShouldThrowForBeforebeginAndAfterendComment(Element $element): void
    {
        $this->assertThrows(static function () use ($element): void {
            $element->insertAdjacentHTML('afterend', '<!-- fail -->');
        }, NoModificationAllowedError::class);
        $this->assertThrows(static function () use ($element): void {
            $element->insertAdjacentHTML('beforebegin', '<!-- fail -->');
        }, NoModificationAllowedError::class);
    }

    /**
     * @dataProvider elementProvider
     */
    public function testShouldThrowForBeforebeginAndAfterendElements(Element $element): void
    {
        $this->assertThrows(static function () use ($element): void {
            $element->insertAdjacentHTML('afterend', '<div></div>');
        }, NoModificationAllowedError::class);
        $this->assertThrows(static function () use ($element): void {
            $element->insertAdjacentHTML('beforebegin', '<div></div>');
        }, NoModificationAllowedError::class);
    }

    public function elementProvider(): array
    {
        $document = self::getWindow()->document;
        $child = $document->createElement('div');
        $child->id = 'child';

        return [
            [$child],
            [self::getWindow()->document->documentElement],
        ];
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }
}
