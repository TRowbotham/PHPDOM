<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/innerhtml-04.html
 */
class InnerHTML04Test extends TestCase
{
    public function testInnerHTMLShouldLeaveTheRemovedChildrenAlone(): void
    {
        $document = new HTMLDocument();
        $p = $document->createElement('p');
        $b = $p->appendChild($document->createElement('b'));
        $t = $b->appendChild($document->createTextNode('foo'));
        self::assertIsChild($p, $b);
        self::assertIsChild($b, $t);
        self::assertSame('foo', $t->data);
        $p->innerHTML = '';
        self::assertIsChild($b, $t);
        self::assertSame('foo', $t->data);
    }

    public static function assertIsChild($p, $c): void
    {
        self::assertSame($c, $p->firstChild);
        self::assertSame($p, $c->parentNode);
    }
}
