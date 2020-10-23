<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\other;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

class ParsingMisnestedTagsTest extends TestCase
{
    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#misnested-tags:-b-i-/b-/i
     */
    public function test1(): void
    {
        $document = new HTMLDocument();
        $document->appendChild($document->implementation->createDocumentType('html', '', ''));
        $html = $document->appendChild($document->createElement('html'));
        $html->appendChild($document->createElement('head'));
        $body = $html->appendChild($document->createElement('body'));
        $p = $body->appendChild($document->createElement('p'));
        $p->appendChild($document->createTextNode('1'));
        $b = $p->appendChild($document->createElement('b'));
        $b->appendChild($document->createTextNode('2'));
        $b->appendChild($document->createElement('i'))
            ->appendChild($document->createTextNode('3'));
        $p->appendChild($document->createElement('i'))
            ->appendChild($document->createTextNode('4'));
        $p->appendChild($document->createTextNode('5'));

        $newBody = $document->createElement('body');
        $newBody->innerHTML = '<p>1<b>2<i>3</b>4</i>5</p>';
        self::assertTrue($body->isEqualNode($newBody));
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#misnested-tags:-b-p-/b-/p
     */
    public function test2(): void
    {
        $document = new HTMLDocument();
        $document->appendChild($document->implementation->createDocumentType('html', '', ''));
        $html = $document->appendChild($document->createElement('html'));
        $html->appendChild($document->createElement('head'));
        $body = $html->appendChild($document->createElement('body'));
        $b = $body->appendChild($document->createElement('b'));
        $b->appendChild($document->createTextNode('1'));
        $p = $body->appendChild($document->createElement('p'));
        $p->appendChild($document->createElement('b'))
            ->appendChild($document->createTextNode('2'));
        $p->appendChild($document->createTextNode('3'));

        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<b>1<p>2</b>3</p>
TEST_HTML;
        $parser = new DOMParser();
        $doc = $parser->parseFromString($html, 'text/html');
        $newBody = $doc->body;
        self::assertTrue($document->body->isEqualNode($newBody));
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#unexpected-markup-in-tables
     */
    public function test3(): void
    {
        $document = new HTMLDocument();
        $document->appendChild($document->implementation->createDocumentType('html', '', ''));
        $html = $document->appendChild($document->createElement('html'));
        $html->appendChild($document->createElement('head'));
        $body = $html->appendChild($document->createElement('body'));
        $body->appendChild($document->createElement('b'));
        $body->appendChild($document->createElement('b'))
            ->appendChild($document->createTextNode('bbb'));
        $body->appendChild($document->createElement('table'))
            ->appendChild($document->createElement('tbody'))
            ->appendChild($document->createElement('tr'))
            ->appendChild($document->createElement('td'))
            ->appendChild($document->createTextNode('aaa'));
        $body->appendChild($document->createElement('b'))
            ->appendChild($document->createTextNode('ccc'));

        $newBody = $document->createElement('body');
        $newBody->innerHTML = '<table><b><tr><td>aaa</td></tr>bbb</table>ccc';
        self::assertTrue($body->isEqualNode($newBody));
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#unclosed-formatting-elements
     */
    public function test4(): void
    {
        $document = new HTMLDocument();
        $document->appendChild($document->implementation->createDocumentType('html', '', ''));
        $html = $document->appendChild($document->createElement('html'));
        $html->appendChild($document->createElement('head'));
        $body = $html->appendChild($document->createElement('body'));
        $b = $body->appendChild($document->createElement('p'))
            ->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b = $b->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b = $b->appendChild($document->createElement('b'))
            ->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b = $b->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b->appendChild($document->createElement('b'))
            ->appendChild($document->createTextNode("X\n"));

        $b = $body->appendChild($document->createElement('p'))
            ->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b = $b->appendChild($document->createElement('b'))
            ->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b = $b->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b->appendChild($document->createElement('b'))
            ->appendChild($document->createTextNode("X\n"));

        $b = $body->appendChild($document->createElement('p'))
            ->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b = $b->appendChild($document->createElement('b'))
            ->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b = $b->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b = $b->appendChild($document->createElement('b'))
            ->appendChild($document->createElement('b'))
            ->appendChild($document->createElement('b'));
        $b->className = 'x';
        $b->appendChild($document->createElement('b'))
            ->appendChild($document->createTextNode("X\n"));

        $body->appendChild($document->createElement('p'))
            ->appendChild($document->createTextNode("X"));

        $newBody = $document->createElement('body');
        $newBody->innerHTML = <<<'TEST_HTML'
<p><b class=x><b class=x><b><b class=x><b class=x><b>X
<p>X
<p><b><b class=x><b>X
<p></b></b></b></b></b></b>X
TEST_HTML;
        self::assertTrue($body->isEqualNode($newBody));
    }
}
