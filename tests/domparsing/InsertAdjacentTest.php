<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Generator;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function array_keys;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/insert-adjacent.html
 */
class InsertAdjacentTest extends TestCase
{
    use WindowTrait;

    private const POSSIBLE_POSITIONS = [
        'beforebegin' => 'previousSibling',
        'afterbegin'  => 'firstChild',
        'beforeend'   => 'lastChild',
        'afterend'    => 'nextSibling',
    ];

    /**
     * @dataProvider positionProvider
     */
    public function testInsertAdjacentHTML(string $position): void
    {
        $document = self::getWindow()->document;
        // $el = $document->querySelector('#element');
        $el = $document->getElementById('element');
        $html = '<h3>' . $position . '</h3>';
        $el->insertAdjacentHTML($position, $html);
        $heading = $document->createElement('h3');
        $heading->innerHTML = $position;
        self::assertSame('H3', $el->{self::POSSIBLE_POSITIONS[$position]}->nodeName);
        self::assertSame(Node::TEXT_NODE, $el->{self::POSSIBLE_POSITIONS[$position]}->firstChild->nodeType);
    }

    public function positionProvider(): Generator
    {
        foreach (array_keys(self::POSSIBLE_POSITIONS) as $position) {
            yield [$position];
        }
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'insert-adjacent.html';
    }
}
