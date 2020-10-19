<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-whitespace-class-names.html
 */
class GetElementsByClassNameWhitespaceClassNamesTest extends NodeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider spanNodesProvider
     */
    public function testPassingUnicodeToGetElementsByClassNameStillFindsTheSpan(
        string $charName,
        Element $span
    ): void {
        $className = $span->getAttribute('class');
        self::assertSame(1, mb_strlen($className, 'utf-8'));
        $shouldBeSpan = self::getWindow()->document->getElementsByClassName($className);
        self::assertEquals([$span], iterator_to_array($shouldBeSpan));
    }

    public function spanNodesProvider(): Generator
    {
        $document = self::getWindow()->document;
        // $spans = $document->querySelector('span');
        $spans = $document->getElementsByTagName('span');

        foreach ($spans as $span) {
            yield [$span->textContent, $span];
        }
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-whitespace-class-names.html';
    }
}
