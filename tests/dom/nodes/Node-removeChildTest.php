<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use stdClass;
use TypeError;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-removeChild.html
 */
class NodeRemoveChildTest extends TestCase
{
    use Creators;
    use DocumentGetter;

    public function test1()
    {
        $document = $this->getHTMLDocument();

        foreach ($this->creators as $creator) {
            $doc = $document;
            $s = $doc->{$creator}('a');
            $this->assertSame($doc, $s->ownerDocument);
            $this->assertThrows(function () use ($document, $s) {
                $document->body->removeChild($s);
            }, NotFoundError::class);
            $this->assertSame($doc, $s->ownerDocument);
        }
    }

    public function test2()
    {
        $this->markTestSkipped('We don\'t support frames yet.');
        $document = $this->getHTMLDocument();

        foreach ($this->creators as $creator) {
            $doc = $fames[0]->document;
            $s = $doc->{$creator}('a');
            $this->assertSame($doc, $s->ownerDocument);
            $this->assertThrows(function () use ($document, $s) {
                $document->body->removeChild($s);
            }, NotFoundError::class);
            $this->assertSame($doc, $s->ownerDocument);
        }
    }

    public function test3()
    {
        $document = $this->getHTMLDocument();

        foreach ($this->creators as $creator) {
            $doc = $document->implementation->createHTMLDocument();
            $s = $doc->{$creator}('a');
            $this->assertSame($doc, $s->ownerDocument);
            $this->assertThrows(function () use ($document, $s) {
                $document->body->removeChild($s);
            }, NotFoundError::class);
            $this->assertSame($doc, $s->ownerDocument);
        }
    }

    /**
     * Passing a value that is not a Node reference to removeChild() should
     * throw a TypeError.
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $this->assertThrows(function () use ($document) {
            $document->removeChild(null);
        }, TypeError::class);
        $this->assertThrows(function () use ($document) {
            $obj = new stdClass();
            $obj->a = 'b';
            $document->body->removeChild($obj);
        }, TypeError::class);
    }
}
