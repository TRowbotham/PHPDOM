<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function array_map;
use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-32.htm
 */
class GetElementsByClassName32Test extends NodeTestCase
{
    use WindowTrait;

    public function testCannotFindTheClassName(): void
    {
        $document = self::getWindow()->document;
        $p = $document->createElement('p');
        $p->className = 'unknown';
        $document->body->appendChild($p);
        $elements = $document->getElementsByClassName('first-p');
        self::assertSame([], iterator_to_array($elements));
    }

    public function testFindsTheClassName(): void
    {
        $document = self::getWindow()->document;
        $p = $document->createElement('p');
        $p->className = 'first-p';
        $document->body->appendChild($p);
        $elements = $document->getElementsByClassName('first-p');
        self::assertSame([$p], iterator_to_array($elements));
    }

    public function testFindsTheSameElementWithMultipleClassNames(): void
    {
        $document = self::getWindow()->document;
        $p = $document->createElement('p');
        $p->className = 'the-p second third';
        $document->body->appendChild($p);

        $elements1 = $document->getElementsByClassName('the-p');
        self::assertSame([$p], iterator_to_array($elements1));

        $elements2 = $document->getElementsByClassName('second');
        self::assertSame([$p], iterator_to_array($elements2));

        $elements3 = $document->getElementsByClassName('third');
        self::assertSame([$p], iterator_to_array($elements3));
    }

    public function testDoesNotGetConfusedByNumericIds(): void
    {
        $document = self::getWindow()->document;
        $elements = $document->getElementsByClassName('df-article');

        self::assertSame(3, $elements->length);
        self::assertSame(['1', '2', '3'], array_map(static function (Element $el): string {
            return $el->id;
        }, iterator_to_array($elements)));
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-32.html';
    }
}
