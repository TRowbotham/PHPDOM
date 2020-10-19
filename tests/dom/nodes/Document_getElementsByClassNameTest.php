<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Tests\dom\DocumentGetter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-getElementsByClassName.html
 */
class Document_getElementsByClassNameTest extends NodeTestCase
{
    use DocumentGetter;

    public function testGetElementsByClassNameShouldBeALiveCollection(): void
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('a');
        $b = $document->createElement('b');
        $a->className = 'foo';
        $document->body->appendChild($a);

        $l = $document->getElementsByClassName('foo');
        self::assertInstanceOf(HTMLCollection::class, $l);
        self::assertSame(1, $l->length);

        $b->className = 'foo';
        $document->body->appendChild($b);
        self::assertSame(2, $l->length);

        $document->body->removeChild($b);
        self::assertSame(1, $l->length);

        $document->body->removeChild($a);
    }
}
