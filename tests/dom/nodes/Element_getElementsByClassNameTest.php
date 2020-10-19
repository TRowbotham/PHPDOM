<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\NodeList;
use Rowbot\DOM\Tests\dom\DocumentGetter;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-getElementsByClassName.html
 */
class Element_getElementsByClassNameTest extends NodeTestCase
{
    use DocumentGetter;

    public function testGetElementsByClassNameShouldWorkOnDisconnectedSubtrees(): void
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('a');
        $b = $document->createElement('b');
        $b->className = 'foo';
        $a->appendChild($b);
        $list = $a->getElementsByClassName('foo');
        self::assertSame([$b], iterator_to_array($list));
        $secondList = $a->getElementsByClassName('foo');
        self::assertTrue($list === $secondList || $list !== $secondList);
    }

    public function testInterfaceShouldBeCorrect(): void
    {
        $list = $this->getHTMLDocument()->getElementsByClassName('foo');
        self::assertNotInstanceOf(NodeList::class, $list);
        self::assertInstanceOf(HTMLCollection::class, $list);
    }

    public function testGetElementsByClassNameShouldWorkBeALiveCollection(): void
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement("a");
        $b = $document->createElement("b");
        $c = $document->createElement("c");
        $b->className = "foo";
        $document->body->appendChild($a);
        $a->appendChild($b);

        $l = $a->getElementsByClassName('foo');
        self::assertInstanceOf(HTMLCollection::class, $l);
        self::assertSame(1, $l->length);

        $c->className = 'foo';
        $a->appendChild($c);
        self::assertSame(2, $l->length);

        $a->removeChild($c);
        self::assertSame(1, $l->length);
        $document->body->removeChild($a);
    }
}
