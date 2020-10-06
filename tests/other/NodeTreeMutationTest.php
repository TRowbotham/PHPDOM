<?php

namespace Rowbot\DOM\Tests\other;

use Rowbot\DOM\Element\HTML\HTMLDivElement;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

use function iterator_to_array;

class NodeTreeMutationTest extends TestCase
{
    use DocumentGetter;

    public function testAppendChild(): HTMLDivElement
    {
        $document = $this->getHTMLDocument();
        $parent = $document->createElement('div');
        $a = $document->createElement('p');
        $b = $document->createElement('span');
        $c = $document->createElement('h1');

        // save as variable to check collection's livelyness
        $childNodes = $parent->childNodes;

        $parent->appendChild($a);
        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($a, $parent->lastChild);
        $this->assertCount(1, $childNodes);
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($a, $childNodes->item(0));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertNull($a->nextSibling);

        $parent->appendChild($b);
        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($b, $parent->lastChild);
        $this->assertCount(2, $childNodes);
        $this->assertSame([$a, $b], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($b, $childNodes[1]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($b, $childNodes->item(1));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertNull($b->nextSibling);

        $parent->appendChild($c);
        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($c, $parent->lastChild);
        $this->assertCount(3, $childNodes);
        $this->assertSame([$a, $b, $c], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($b, $childNodes[1]);
        $this->assertSame($c, $childNodes[2]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($b, $childNodes->item(1));
        $this->assertSame($c, $childNodes->item(2));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertSame($c, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($b, $c->previousSibling);
        $this->assertNull($c->nextSibling);

        return $parent;
    }

    /**
     * @depends testAppendChild
     */
    public function testAppendChildWithMiddleChildToSameParent(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c] = iterator_to_array($childNodes);
        $parent->appendChild($b);

        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($b, $parent->lastChild);
        $this->assertCount(3, $childNodes);
        $this->assertSame([$a, $c, $b], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($c, $childNodes[1]);
        $this->assertSame($b, $childNodes[2]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($c, $childNodes->item(1));
        $this->assertSame($b, $childNodes->item(2));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($c, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($c, $b->previousSibling);
        $this->assertNull($b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($a, $c->previousSibling);
        $this->assertSame($b, $c->nextSibling);
    }

    /**
     * @depends testAppendChild
     */
    public function testAppendChildWithFirstChildToSameParent(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c] = iterator_to_array($childNodes);
        $parent->appendChild($a);

        // test parent
        $this->assertSame($b, $parent->firstChild);
        $this->assertSame($a, $parent->lastChild);
        $this->assertCount(3, $childNodes);
        $this->assertSame([$b, $c, $a], iterator_to_array($childNodes));
        $this->assertSame($b, $childNodes[0]);
        $this->assertSame($c, $childNodes[1]);
        $this->assertSame($a, $childNodes[2]);
        $this->assertSame($b, $childNodes->item(0));
        $this->assertSame($c, $childNodes->item(1));
        $this->assertSame($a, $childNodes->item(2));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertSame($c, $a->previousSibling);
        $this->assertNull($a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertNull($b->previousSibling);
        $this->assertSame($c, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($b, $c->previousSibling);
        $this->assertSame($a, $c->nextSibling);
    }

    /**
     * @depends testAppendChild
     */
    public function testAppendChildWithLastChildToSameParent(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c] = iterator_to_array($childNodes);
        $parent->appendChild($c);

        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($c, $parent->lastChild);
        $this->assertCount(3, $childNodes);
        $this->assertSame([$a, $b, $c], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($b, $childNodes[1]);
        $this->assertSame($c, $childNodes[2]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($b, $childNodes->item(1));
        $this->assertSame($c, $childNodes->item(2));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertSame($c, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($b, $c->previousSibling);
        $this->assertNull($c->nextSibling);
    }

    public function testRemoveChildWithNodeNotInParent(): void
    {
        $document = $this->getHTMLDocument();
        $parent = $document->createElement('div');
        $this->expectException(NotFoundError::class);
        $parent->removeChild($document->createElement('div'));
    }

    /**
     * @depends testAppendChild
     */
    public function testRemoveChildWithFirstChild(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c] = iterator_to_array($childNodes);
        $parent->removeChild($a);

        // test parent
        $this->assertSame($b, $parent->firstChild);
        $this->assertSame($c, $parent->lastChild);
        $this->assertCount(2, $childNodes);
        $this->assertSame([$b, $c], iterator_to_array($childNodes));
        $this->assertSame($b, $childNodes[0]);
        $this->assertSame($c, $childNodes[1]);
        $this->assertSame($b, $childNodes->item(0));
        $this->assertSame($c, $childNodes->item(1));

        // test child a
        $this->assertNull($a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertNull($a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertNull($b->previousSibling);
        $this->assertSame($c, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($b, $c->previousSibling);
        $this->assertNull($c->nextSibling);
    }

    /**
     * @depends testAppendChild
     */
    public function testRemoveChildWithMiddleChild(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c] = iterator_to_array($childNodes);
        $parent->removeChild($b);

        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($c, $parent->lastChild);
        $this->assertCount(2, $childNodes);
        $this->assertSame([$a, $c], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($c, $childNodes[1]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($c, $childNodes->item(1));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($c, $a->nextSibling);

        // test child b
        $this->assertNull($b->parentNode);
        $this->assertNull($b->previousSibling);
        $this->assertNull($b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($a, $c->previousSibling);
        $this->assertNull($c->nextSibling);
    }

    /**
     * @depends testAppendChild
     */
    public function testRemoveChildWithLastChild(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c] = iterator_to_array($childNodes);
        $parent->removeChild($c);

        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($b, $parent->lastChild);
        $this->assertCount(2, $childNodes);
        $this->assertSame([$a, $b], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($b, $childNodes[1]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($b, $childNodes->item(1));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertNull($b->nextSibling);

        // test child c
        $this->assertNull($c->parentNode);
        $this->assertNull($c->previousSibling);
        $this->assertNull($c->nextSibling);
    }

    public function testInsertBeforeBeforeChildNotInParent(): void
    {
        $document = $this->getHTMLDocument();
        $parent = $document->createElement('div');
        $this->expectException(NotFoundError::class);
        $parent->insertBefore($document->createElement('a'), $document->createElement('span'));
    }

    /**
     * @depends testAppendChild
     */
    public function testInsertBeforeBeforeFirstChild(HTMLDivElement $parent): HTMLDivElement
    {
        $document = $parent->ownerDocument;
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c] = iterator_to_array($childNodes);
        $d = $document->createElement('a');
        $parent->insertBefore($d, $a);

        // test parent
        $this->assertSame($d, $parent->firstChild);
        $this->assertSame($c, $parent->lastChild);
        $this->assertCount(4, $childNodes);
        $this->assertSame([$d, $a, $b, $c], iterator_to_array($childNodes));
        $this->assertSame($d, $childNodes[0]);
        $this->assertSame($a, $childNodes[1]);
        $this->assertSame($b, $childNodes[2]);
        $this->assertSame($c, $childNodes[3]);
        $this->assertSame($d, $childNodes->item(0));
        $this->assertSame($a, $childNodes->item(1));
        $this->assertSame($b, $childNodes->item(2));
        $this->assertSame($c, $childNodes->item(3));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertSame($d, $a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertSame($c, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($b, $c->previousSibling);
        $this->assertNull($c->nextSibling);

        // test child d
        $this->assertSame($parent, $d->parentNode);
        $this->assertNull($d->previousSibling);
        $this->assertSame($a, $d->nextSibling);

        return $parent;
    }

    /**
     * @depends testInsertBeforeBeforeFirstChild
     */
    public function testInsertBeforeBeforeSecondChildKeepingSamePositions(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c, $d] = iterator_to_array($childNodes);
        $parent->insertBefore($a, $b);

        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($d, $parent->lastChild);
        $this->assertCount(4, $childNodes);
        $this->assertSame([$a, $b, $c, $d], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($b, $childNodes[1]);
        $this->assertSame($c, $childNodes[2]);
        $this->assertSame($d, $childNodes[3]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($b, $childNodes->item(1));
        $this->assertSame($c, $childNodes->item(2));
        $this->assertSame($d, $childNodes->item(3));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertSame($c, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($b, $c->previousSibling);
        $this->assertSame($d, $c->nextSibling);

        // test child d
        $this->assertSame($parent, $d->parentNode);
        $this->assertSame($c, $d->previousSibling);
        $this->assertNull($d->nextSibling);
    }

    /**
     * @depends testInsertBeforeBeforeFirstChild
     */
    public function testInsertBeforeBeforeSelf(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c, $d] = iterator_to_array($childNodes);
        $parent->insertBefore($b, $b);

        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($d, $parent->lastChild);
        $this->assertCount(4, $childNodes);
        $this->assertSame([$a, $b, $c, $d], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($b, $childNodes[1]);
        $this->assertSame($c, $childNodes[2]);
        $this->assertSame($d, $childNodes[3]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($b, $childNodes->item(1));
        $this->assertSame($c, $childNodes->item(2));
        $this->assertSame($d, $childNodes->item(3));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertSame($c, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($b, $c->previousSibling);
        $this->assertSame($d, $c->nextSibling);

        // test child d
        $this->assertSame($parent, $d->parentNode);
        $this->assertSame($c, $d->previousSibling);
        $this->assertNull($d->nextSibling);
    }

    /**
     * @depends testInsertBeforeBeforeFirstChild
     */
    public function testInsertBeforeBeforeLastChildKeepingSamePosition(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c, $d] = iterator_to_array($childNodes);
        $parent->insertBefore($c, $d);

        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($d, $parent->lastChild);
        $this->assertCount(4, $childNodes);
        $this->assertSame([$a, $b, $c, $d], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($b, $childNodes[1]);
        $this->assertSame($c, $childNodes[2]);
        $this->assertSame($d, $childNodes[3]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($b, $childNodes->item(1));
        $this->assertSame($c, $childNodes->item(2));
        $this->assertSame($d, $childNodes->item(3));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertSame($c, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($b, $c->previousSibling);
        $this->assertSame($d, $c->nextSibling);

        // test child d
        $this->assertSame($parent, $d->parentNode);
        $this->assertSame($c, $d->previousSibling);
        $this->assertNull($d->nextSibling);
    }

    /**
     * @depends testAppendChild
     */
    public function testInsertBeforeBeforeLastChild(HTMLDivElement $parent): void
    {
        $parent = $parent->cloneNode(true);
        $childNodes = $parent->childNodes;
        [$a, $b, $c] = iterator_to_array($childNodes);
        $d = $parent->ownerDocument->createElement('table');
        $parent->insertBefore($d, $c);

        // test parent
        $this->assertSame($a, $parent->firstChild);
        $this->assertSame($c, $parent->lastChild);
        $this->assertCount(4, $childNodes);
        $this->assertSame([$a, $b, $d, $c], iterator_to_array($childNodes));
        $this->assertSame($a, $childNodes[0]);
        $this->assertSame($b, $childNodes[1]);
        $this->assertSame($d, $childNodes[2]);
        $this->assertSame($c, $childNodes[3]);
        $this->assertSame($a, $childNodes->item(0));
        $this->assertSame($b, $childNodes->item(1));
        $this->assertSame($d, $childNodes->item(2));
        $this->assertSame($c, $childNodes->item(3));

        // test child a
        $this->assertSame($parent, $a->parentNode);
        $this->assertNull($a->previousSibling);
        $this->assertSame($b, $a->nextSibling);

        // test child b
        $this->assertSame($parent, $b->parentNode);
        $this->assertSame($a, $b->previousSibling);
        $this->assertSame($d, $b->nextSibling);

        // test child c
        $this->assertSame($parent, $c->parentNode);
        $this->assertSame($d, $c->previousSibling);
        $this->assertNull($c->nextSibling);

        // test child d
        $this->assertSame($parent, $d->parentNode);
        $this->assertSame($b, $d->previousSibling);
        $this->assertSame($c, $d->nextSibling);
    }
}
