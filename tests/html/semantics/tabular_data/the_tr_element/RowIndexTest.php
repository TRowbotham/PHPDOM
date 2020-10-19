<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_tr_element;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-tr-element/rowIndex.html
 */
class RowIndexTest extends TestCase
{
    use DocumentGetter;

    public function test1(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElement('table')
            ->appendChild($document->createElement('div'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }

    public function test2(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElement('table')
            ->appendChild($document->createElement('thead'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(0, $row->rowIndex);
    }

    public function test3(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElement('table')
            ->appendChild($document->createElement('tbody'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(0, $row->rowIndex);
    }

    public function test4(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElement('table')
            ->appendChild($document->createElement('tfoot'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(0, $row->rowIndex);
    }

    public function test5(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElement('table')
            ->appendChild($document->createElement('tr'));
        self::assertSame(0, $row->rowIndex);
    }

    public function test6(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElementNS('', 'table')
            ->appendChild($document->createElement('thead'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }

    public function test7(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElementNS('', 'table')
            ->appendChild($document->createElement('tbody'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }

    public function test8(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElementNS('', 'table')
            ->appendChild($document->createElement('tfoot'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }

    public function test9(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElementNS('', 'table')
            ->appendChild($document->createElement('thead'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }

    public function test10(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElementNS('', 'table')
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }

    public function test11(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElement('table')
            ->appendChild($document->createElementNS('', 'thead'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }

    public function test12(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElement('table')
            ->appendChild($document->createElementNS('', 'tbody'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }

    public function test13(): void
    {
        $document = $this->getHTMLDocument();
        $row = $document->createElement('table')
            ->appendChild($document->createElementNS('', 'tfoot'))
            ->appendChild($document->createElement('tr'));
        self::assertSame(-1, $row->rowIndex);
    }
}
