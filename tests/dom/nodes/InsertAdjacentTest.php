<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Tests\dom\WindowTrait;
use TypeError;

use function array_keys;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/insert-adjacent.html#L48
 */
class InsertAdjacentTest extends NodeTestCase
{
    use WindowTrait;

    private const POSSIBLE_POSITIONS = [
        'beforebegin' => 'previousSibling',
        'afterbegin'  => 'firstChild',
        'beforeend'   => 'lastChild',
        'afterend'    => 'nextSibling',
    ];
    private const TEXTS = [
        'beforebegin' => 'raclette',
        'afterbegin'  => 'tartiflette',
        'beforeend'   => 'lasagne',
        'afterend'    => 'gateau aux pommes',
    ];

    private static $el;

    /**
     * @dataProvider positionProvider
     */
    public function testInsertAdjacentElement(Element $div, string $position): void
    {
        $div->id = self::TEXTS[$position];
        $el = $this->getEl();
        $el->insertAdjacentElement($position, $div);
        $this->assertSame(self::TEXTS[$position], $el->{self::POSSIBLE_POSITIONS[$position]}->id);
    }

    /**
     * @dataProvider positionProvider
     */
    public function testInsertAdjacentText(Element $div, string $position): void
    {
        $el = $this->getEl();
        $div->id = self::TEXTS[$position];
        $el->insertAdjacentText($position, self::TEXTS[$position]);
        $this->assertSame(
            self::TEXTS[$position],
            $el->{self::POSSIBLE_POSITIONS[$position]}->textContent
        );
    }

    public function testInsertAdjacentElementWithInvalidObject(): void
    {
        $this->expectException(TypeError::class);
        $this->getEl()->insertAdjacentElement(
            'afterbegin',
            self::getWindow()->document->implementation->createDocumentType('html', '', '')
        );
    }

    public function testInsertAdjacentElementWithInvalidCaller(): void
    {
        $document = self::getWindow()->document;
        $el = $document->implementation->createHTMLDocument()->documentElement;
        $this->expectException(HierarchyRequestError::class);
        $el->insertAdjacentElement('beforebegin', $document->createElement('banane'));
    }

    public function testInsertAdjacentTextWithInvalidCaller(): void
    {
        $document = self::getWindow()->document;
        $el = $document->implementation->createHTMLDocument()->documentElement;
        $this->expectException(HierarchyRequestError::class);
        $el->insertAdjacentText('beforebegin', 'tomate farcie');
    }

    public function testInsertAdjacentElementInvalidSyntax(): void
    {
        $div = self::getWindow()->document->createElement('h3');
        $this->expectException(SyntaxError::class);
        $this->getEl()->insertAdjacentElement('heeeee', $div);
    }

    public function testInsertAdjacentTextInvalidSyntax(): void
    {
        $div = self::getWindow()->document->createElement('h3');
        $this->expectException(SyntaxError::class);
        $this->getEl()->insertAdjacentText('hoooo', 'magret de canard');
    }

    public function testInsertAdjacentTextReturnsNull(): void
    {
        $div = self::getWindow()->document->createElement('div');
        $this->assertNull($div->insertAdjacentElement('beforebegin', $this->getEl()));
    }

    public function positionProvider(): Generator
    {
        $div = self::getWindow()->document->createElement('h3');

        foreach (array_keys(self::POSSIBLE_POSITIONS) as $position) {
            yield [$div, $position];
        }
    }

    public function getEl()
    {
        // return self::getWindow()->document->querySelector('#element');
        return self::getWindow()->document->getElementById('element');
    }

    public static function getDocumentName(): string
    {
        return 'insert-adjacent.html';
    }
}
