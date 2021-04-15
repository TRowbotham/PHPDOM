<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/tBodies.html
 */
class TBodiesTest extends TestCase
{
    use WindowTrait;

    public function testTBodies(): void
    {
        self::markTestSkipped('We don\'t support parsing xml documents yet');

        $document = self::getWindow()->document;

        $text = '<html xmlns="http://www.w3.org/1999/xhtml">'
            . '  <head>'
            . '    <title>Virtual Library</title>'
            . '  </head>'
            . '  <body>'
            . '    <table id="mytable" border="1">'
            . '      <tbody>'
            . '        <tr><td>Cell 1</td><td>Cell 2</td></tr>'
            . '        <tr><td>Cell 3</td><td>Cell 4</td></tr>'
            . '      </tbody>'
            . '    </table>'
            . '  </body>'
            . '</html>';

        $parser = new DOMParser();
        $doc = $parser->parseFromString($text, 'text/xml');

        // import <table>
        $table = $doc->documentElement->getElementsByTagName('table')[0];
        $mytable = $document->body->appendChild($document->importNode($table, true));

        self::assertSame(1, $mytable->tBodies->length);
        $tbody = $document->createElement('tbody');
        $mytable->appendChild($tbody);
        $tr = $tbody->insertRow(-1);
        $tr->insertCell(-1)->appendChild($document->createTextNode('Cell 5'));
        $tr->insertCell(-1)->appendChild($document->createTextNode('Cell 6'));
        self::assertSame(2, $mytable->tBodies->length);
        self::assertSame(3, $mytable->rows->length);
        self::assertSame(2, $tr->rowIndex);
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'tBodies.html';
    }
}
