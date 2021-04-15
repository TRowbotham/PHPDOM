<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Text-wholeText.html
 */
class TextWholeTextTest extends TestCase
{
    public function testWholeText(): void
    {
        $document = new HTMLDocument();
        $parent = $document->createElement('div');

        $t1 = $document->createTextNode('a');
        $t2 = $document->createTextNode('b');
        $t3 = $document->createTextNode('c');

        $this->assertSame($t1->textContent, $t1->wholeText);

        $parent->appendChild($t1);

        $this->assertSame($t1->textContent, $t1->wholeText);

        $parent->appendChild($t2);

        $this->assertSame($t1->textContent . $t2->textContent, $t1->wholeText);
        $this->assertSame($t1->textContent . $t2->textContent, $t2->wholeText);

        $parent->appendChild($t3);

        $this->assertSame($t1->textContent . $t2->textContent . $t3->textContent, $t1->wholeText);
        $this->assertSame($t1->textContent . $t2->textContent . $t3->textContent, $t2->wholeText);
        $this->assertSame($t1->textContent . $t2->textContent . $t3->textContent, $t3->wholeText);

        $a = $document->createElement('div');
        $a->textContent = "I'm an Anchor";
        $parent->insertBefore($a, $t3);

        $span = $document->createElement('span');
        $span->textContent = "I'm a Span";
        $parent->appendChild($document->createElement('span'));

        $this->assertSame($t1->textContent . $t2->textContent, $t1->wholeText);
        $this->assertSame($t1->textContent . $t2->textContent, $t2->wholeText);
        $this->assertSame($t3->textContent, $t3->wholeText);
    }
}
