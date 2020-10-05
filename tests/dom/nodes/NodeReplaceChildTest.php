<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use TypeError;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-replaceChild.html
 */
class NodeReplaceChildTest extends TestCase
{
    use DocumentGetter;

    /**
     * Passing null to replaceChild() should throw a TypeError.
     */
    public function test1()
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');

        $this->assertThrows(function () use ($a) {
            $a->replaceChild(null, null);
        }, TypeError::class);

        $b = $document->createElement('div');

        $this->assertThrows(function () use ($a, $b) {
            $a->replaceChild($b, null);
        }, TypeError::class);
        $this->assertThrows(function () use ($a, $b) {
            $a->replaceChild(null, $b);
        }, TypeError::class);
    }

    /**
     * If childs parent is not the context node, a NotFoundError exception
     * should be thrown.
     */
    public function test2()
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');
        $b = $document->createElement('div');
        $c = $document->createElement('div');
        $this->assertThrows(function () use ($a, $b, $c) {
            $a->replaceChild($b, $c);
        }, NotFoundError::class);

        $d = $document->createElement('div');
        $d->appendChild($b);
        $this->assertThrows(function () use ($a, $b, $c) {
            $a->replaceChild($b, $c);
        }, NotFoundError::class);
        $this->assertThrows(function () use ($a, $b) {
            $a->replaceChild($b, $a);
        }, NotFoundError::class);
    }

    public function getNodes()
    {
        $document = $this->getHTMLDocument();

        return [
            [$document->implementation->createDocumentType('html', '', '')],
            [$document->createTextNode('text')],
            [$document->implementation->createDocument(null, 'foo', null)->createProcessingInstruction('foo', 'bar')],
            [$document->createComment('comment')]
        ];
    }

    /**
     * If the context node is not a node that can contain children, a
     * HierarchyRequestError exception should be thrown.
     *
     * @dataProvider getNodes
     */
    public function test3($node)
    {
        $this->expectException(HierarchyRequestError::class);
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');
        $b = $document->createElement('div');
        $node->replaceChild($a, $b);
    }

    /**
     * if node is an inclusive ancestor of the context node, a
     * HierarchyRequestError should be thrown.
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');
        $b = $document->createElement('div');

        $this->assertThrows(function () use ($a) {
            $a->replaceChild($a, $a);
        }, HierarchyRequestError::class);

        $a->appendChild($b);
        $this->assertThrows(function () use ($a, $b) {
            $a->replaceChild($a, $b);
        }, HierarchyRequestError::class);

        $c = $document->createElement('div');
        $c->appendChild($a);
        $this->assertThrows(function () use ($a, $b, $c) {
            $a->replaceChild($c, $b);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting a document or text node
     * should thorw a HierarchyRequestError.
     */
    public function test5()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $doc2 = $document->implementation->createHTMLDocument('title2');

        $this->assertThrows(function () use ($doc, $doc2) {
            $doc->replaceChild($doc2, $doc->documentElement);
        }, HierarchyRequestError::class);

        $this->assertThrows(function () use ($doc) {
            $doc->replaceChild(
                $doc->createTextNode('text'),
                $doc->documentElement
            );
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting a DocumentFragment that
     * contains a text node or too many elements should throw a
     * HierarchyRequestError.
     */
    public function test6()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $df->appendChild($doc->createElement('b'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->replaceChild($df, $doc->documentElement);
        }, HierarchyRequestError::class);

        $df = $document->createDocumentFragment();
        $df->appendChild($doc->createTextNode('text'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->replaceChild($df, $doc->documentElement);
        }, HierarchyRequestError::class);

        $df = $document->createDocumentFragment();
        $df->appendChild($doc->createComment('comment'));
        $df->appendChild($doc->createTextNode('text'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->replaceChild($df, $doc->documentElement);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document (without element children), inserting a
     * DocumentFragment that contains multiple elements should throw a
     * HierarchyRequestError.
     */
    public function test7()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $doc->removeChild($doc->documentElement);

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $df->appendChild($doc->createElement('b'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->replaceChild($df, $doc->doctype);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting a DocumentFragment with an
     * element if there already is an element child should throw a
     * HierarchyRequestError.
     */
    public function test8()
    {
        $document = $this->getHTMLDocument();
        // The context node has an element child that is not /child/.
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->appendChild($doc->createComment('foo'));
        $this->assertSame(
            [$doc->doctype, $doc->documentElement, $comment],
            iterator_to_array($doc->childNodes)
        );

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $this->assertThrows(function () use ($doc, $df, $comment) {
            $doc->replaceChild($df, $comment);
        }, HierarchyRequestError::class);

        $this->assertThrows(function () use ($doc, $df) {
            $doc->replaceChild($df, $doc->doctype);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting a DocumentFragment with an
     * element before the doctype should throw a HierarchyRequestError.
     */
    public function test9()
    {
        $document = $this->getHTMLDocument();
        // A doctype is following /child/.
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->insertBefore(
            $doc->createComment('foo'),
            $doc->firstChild
        );
        $doc->removeChild($doc->documentElement);
        $this->assertSame(
            [$comment, $doc->doctype],
            iterator_to_array($doc->childNodes)
        );

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $this->assertThrows(function () use ($doc, $df, $comment) {
            $doc->replaceChild($df, $comment);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting an element if there already
     * is an element child should throw a HierarchyRequestError.
     */
    public function test10()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->appendChild($doc->createComment('foo'));
        $this->assertSame(
            [$doc->doctype, $doc->documentElement, $comment],
            iterator_to_array($doc->childNodes)
        );

        $a = $doc->createElement('a');
        $this->assertThrows(function () use ($doc, $a, $comment) {
            $doc->replaceChild($a, $comment);
        }, HierarchyRequestError::class);

        $this->assertThrows(function () use ($doc, $a) {
            $doc->replaceChild($a, $doc->doctype);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting an element before the
     * doctype should throw a HierarchyRequestError.
     */
    public function test11()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->insertBefore(
            $doc->createComment('foo'),
            $doc->firstChild
        );
        $doc->removeChild($doc->documentElement);
        $this->assertSame(
            [$comment, $doc->doctype],
            iterator_to_array($doc->childNodes)
        );

        $a = $doc->createElement('a');
        $this->assertThrows(function () use ($doc, $a, $comment) {
            $doc->replaceChild($a, $comment);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting a doctype if there already
     * is a doctype child should throw a HierarchyRequestError.
     */
    public function test12()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->insertBefore(
            $doc->createComment('foo'),
            $doc->firstChild
        );
        $this->assertSame(
            [$comment, $doc->doctype, $doc->documentElement],
            iterator_to_array($doc->childNodes)
        );

        $doctype = $document->implementation->createDocumentType('html', '', '');
        $this->assertThrows(function () use ($doc, $doctype, $comment) {
            $doc->replaceChild($doctype, $comment);
        }, HierarchyRequestError::class);

        $this->assertThrows(function () use ($doc, $doctype) {
            $doc->replaceChild($doctype, $doc->documentElement);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting a doctype after the document
     * element should throw a HierarchyRequestError.
     */
    public function test13()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->appendChild($doc->createComment('foo'));
        $doc->removeChild($doc->doctype);
        $this->assertSame(
            [$doc->documentElement, $comment],
            iterator_to_array($doc->childNodes)
        );

        $doctype = $document->implementation->createDocumentType('html', '', '');
        $this->assertThrows(function () use ($doc, $doctype, $comment) {
            $doc->replaceChild($doctype, $comment);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a DocumentFragment, inserting a document or a
     * doctype should throw a HierarchyRequestError.
     */
    public function test14()
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $a = $df->appendChild($document->createElement('a'));

        $doc = $document->implementation->createHTMLDocument('title');
        $this->assertThrows(function () use ($doc, $a, $df) {
            $df->replaceChild($doc, $a);
        }, HierarchyRequestError::class);

        $doctype = $document->implementation->createDocumentType('html', '', '');
        $this->assertThrows(function () use ($doctype, $a, $df) {
            $df->replaceChild($doctype, $a);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is an element, inserting a document or a doctype
     * should throw a HierarchyRequestError.
     */
    public function test15()
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $a = $el->appendChild($document->createElement('a'));

        $doc = $document->implementation->createHTMLDocument('title');
        $this->assertThrows(function () use ($doc, $a, $el) {
            $el->replaceChild($doc, $a);
        }, HierarchyRequestError::class);

        $doctype = $document->implementation->createDocumentType('html', '', '');
        $this->assertThrows(function () use ($doctype, $a, $el) {
            $el->replaceChild($doctype, $a);
        }, HierarchyRequestError::class);
    }

    /**
     * Replacing a node with its next sibling should work (2 children)
     */
    public function test16()
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');
        $b = $document->createElement('div');
        $c = $document->createElement('div');
        $a->appendChild($b);
        $a->appendChild($c);
        $this->assertSame([$b, $c], iterator_to_array($a->childNodes));
        $this->assertSame($b, $a->replaceChild($c, $b));
        $this->assertSame([$c], iterator_to_array($a->childNodes));
    }

    /**
     * Replacing a node with its next sibling should work (4 children)
     */
    public function test17()
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');
        $b = $document->createElement('div');
        $c = $document->createElement('div');
        $d = $document->createElement('div');
        $e = $document->createElement('div');
        $a->appendChild($b);
        $a->appendChild($c);
        $a->appendChild($d);
        $a->appendChild($e);
        $this->assertSame([$b, $c, $d, $e], iterator_to_array($a->childNodes));
        $this->assertSame($c, $a->replaceChild($d, $c));
        $this->assertSame([$b, $d, $e], iterator_to_array($a->childNodes));
    }

    /**
     * Replacing a node with itself should not move the node.
     */
    public function test18()
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');
        $b = $document->createElement('div');
        $c = $document->createElement('div');
        $a->appendChild($b);
        $a->appendChild($c);
        $this->assertSame([$b, $c], iterator_to_array($a->childNodes));
        $this->assertSame($b, $a->replaceChild($b, $b));
        $this->assertSame([$b, $c], iterator_to_array($a->childNodes));
        $this->assertSame($c, $a->replaceChild($c, $c));
        $this->assertSame([$b, $c], iterator_to_array($a->childNodes));
    }

    /**
     * If the context node is a document, inserting a new doctype should work.
     */
    public function test19()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $doctype = $doc->doctype;
        $this->assertSame(
            [$doctype, $doc->documentElement],
            iterator_to_array($doc->childNodes)
        );

        $doc2 = $document->implementation->createHTMLDocument('title2');
        $doctype2 = $doc2->doctype;
        $this->assertSame(
            [$doctype2, $doc2->documentElement],
            iterator_to_array($doc2->childNodes)
        );

        $doc->replaceChild($doc2->doctype, $doc->doctype);
        $this->assertSame(
            [$doctype2, $doc->documentElement],
            iterator_to_array($doc->childNodes)
        );
        $this->assertSame(
            [$doc2->documentElement],
            iterator_to_array($doc2->childNodes)
        );
        $this->assertNull($doctype->parentNode);
        $this->assertSame($doc, $doctype->ownerDocument);
        $this->assertSame($doc, $doctype2->parentNode);
        $this->assertSame($doc, $doctype2->ownerDocument);
    }

    /**
     * Replacing the document element with a DocumentFragment containing a
     * single element should work.
     */
    public function test20()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $df = $doc->createDocumentFragment();
        $a = $df->appendChild($doc->createElement('a'));
        $this->assertSame(
            $doc->documentElement,
            $doc->replaceChild($df, $doc->documentElement)
        );
        $this->assertSame(
            [$doc->doctype, $a],
            iterator_to_array($doc->childNodes)
        );
    }

    /**
     * Replacing the document element with a DocumentFragment contianing a
     * single element and comments should work.
     */
    public function test21()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $df = $doc->createDocumentFragment();
        $a = $df->appendChild($doc->createComment('a'));
        $b = $df->appendChild($doc->createElement('b'));
        $c = $df->appendChild($doc->createComment('c'));
        $this->assertSame(
            $doc->documentElement,
            $doc->replaceChild($df, $doc->documentElement)
        );
        $this->assertSame(
            [$doc->doctype, $a, $b, $c],
            iterator_to_array($doc->childNodes)
        );
    }

    /**
     * Replacing the document element with a single element should work.
     */
    public function test22()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $a = $doc->createElement('a');
        $this->assertSame(
            $doc->documentElement,
            $doc->replaceChild($a, $doc->documentElement)
        );
        $this->assertSame(
            [$doc->doctype, $a],
            iterator_to_array($doc->childNodes)
        );
    }

    /**
     * replaceChild() should work in the presence of mutation events.
     */
    public function test23()
    {
        $this->markTestSkipped('We don\'t yet support mutation events.');
        $document = $this->getHTMLDocument();
        $document->addEventListener('DOMNodeRemoved', function ($e) use ($document) {
            $document->body->appendChild($document->createElement('x'));
        }, false);
        $a = $document->body->firstChild;
        $b = $a->firstChild;
        $c = $b->nextSibling;
        $this->assertSame($b, $a->replaceChild($c, $b));
        $this->assertNull($b->parentNode);
        $this->assertSame($c, $a->firstChild);
        $this->assertSame($a, $c->parentNode);
    }

    /**
     * Replacing an element with a DocumentFragment should allow a child of the
     * DocumentFragment to be found by Id.
     */
    public function test24()
    {
        $document = $this->getHTMLDocument();
        $TEST_ID = 'findme';
        $gBody = $document->getElementsByTagName('body')[0];
        $parent = $document->createElement('div');
        $gBody->appendChild($parent);
        $child = $document->createElement('div');
        $parent->appendChild($child);
        $df = $document->createDocumentFragment();
        $fragChild = $df->appendChild($document->createElement('div'));
        $fragChild->setAttribute('id', $TEST_ID);
        $parent->replaceChild($df, $child);
        $this->assertSame($fragChild, $document->getElementById($TEST_ID));
    }
}
