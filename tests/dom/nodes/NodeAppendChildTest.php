<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use stdClass;
use TypeError;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-appendChild.html
 */
class NodeAppendChildTest extends TestCase
{
    use DocumentGetter;

    public function test1()
    {
        $document = $this->getHTMLDocument();
        $this->assertThrows(static function () use ($document) {
            $document->body->appendChild(null);
        }, TypeError::class);
        $this->assertThrows(static function () use ($document) {
            $obj = new stdClass();
            $obj->a = 'b';
            $document->body->appendChild(null);
        }, TypeError::class);
    }

    public function getLeafNodes()
    {
        $document = $this->getHTMLDocument();

        return [
            [$document->createTextNode('foo'), 'text node'],
            [$document->createComment('foo'), 'comment'],
            [$document->doctype, 'doctype'],
        ];
    }

    /**
     * @dataProvider getLeafNodes
     */
    public function testLeaf($node, $desc)
    {
        $document = $this->getHTMLDocument();
        $this->assertThrows(static function () use ($node) {
            $node->appendChild(null);
        }, TypeError::class);

        $this->assertThrows(static function () use ($document, $node) {
            $node->appendChild($document->createTextNode('fail'));
        }, HierarchyRequestError::class);
    }

    /**
     * Appending a document
     */
    public function test2()
    {
        $this->markTestIncomplete();
        $document = $this->getHTMLDocument();
        // We don't have a window object to access the frames property
        $frameDoc = $frames[0]->document;
        $this->assertThrows(static function () use ($document, $frameDoc) {
            $document->body->appendChild($frameDoc);
        }, HierarchyRequestError::class);
    }

    /**
     * Adopting an orphan
     */
    public function test3()
    {
        $this->markTestIncomplete();
        $document = $this->getHTMLDocument();
        // We don't have a window object to access the frames property
        $frameDoc = $frames[0]->document;
        $s = $frameDoc->createElement('a');
        $this->assertSame($frameDoc, $s->ownerDocument);
        $document->body->appendChild($s);
        $this->assertSame($document, $s->ownerDocument);
    }

    /**
     * Adopting a non-orphan
     */
    public function test4()
    {
        $this->markTestIncomplete();
        $document = $this->getHTMLDocument();
        // We don't have a window object to access the frames property
        $frameDoc = $frames[0]->document;
        $s = $frameDoc->createElement('b');
        $this->assertSame($frameDoc, $s->ownerDocument);
        $frameDoc->body->appendChild($s);
        $this->assertSame($frameDoc, $s->ownerDocument);
        $document->body->appendChild($s);
        $this->assertSame($document, $s->ownerDocument);
    }
}
