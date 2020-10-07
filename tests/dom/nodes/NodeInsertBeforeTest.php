<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use stdClass;
use TypeError;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-insertBefore.html
 */
class NodeInsertBeforeTest extends NodeTestCase
{
    use DocumentGetter;
    use PreinsertionValidationNotFoundTrait;
    use PreinsertionValidationHierarchyTrait;

    /**
     * Calling insertBefore() with a non-Node first argument must throw
     * TypeError.
     */
    public function test1()
    {
        $document = $this->getHTMLDocument();

        $this->assertThrows(function () use ($document) {
            $document->body->insertBefore(null, null);
        }, TypeError::class);
        $this->assertThrows(function () use ($document) {
            $document->body->insertBefore(null, $document->body->firstChild);
        }, TypeError::class);
        $this->assertThrows(function () use ($document) {
            $obj = new stdClass();
            $obj->a = 'b';
            $document->body->insertBefore($obj, $document->body->firstChild);
        }, TypeError::class);
    }

    public function getLeafNodes()
    {
        $document = $this->getHTMLDocument();

        return [
            ['DocumentType', function () use ($document) {
                return $document->doctype;
            }],
            ['Text', function () use ($document) {
                return $document->createTextNode('Foo');
            }],
            ['Comment', function () use ($document) {
                return $document->createComment('Foo');
            }],
            ['ProcessingInstruction', function () use ($document) {
                return $document->createProcessingInstruction('foo', 'bar');
            }]
        ];
    }

    /**
     * @dataProvider getLeafNodes
     */
    public function testLeafNode($nodeName, $createNodeFunction)
    {
        $document = $this->getHTMLDocument();
        $node = $createNodeFunction();

        $this->assertThrows(function () use ($node) {
            $node->insertBefore(null, null);
        }, TypeError::class);
        $this->assertThrows(function () use ($node, $document) {
            $node->insertBefore($document->createTextNode('fail'), null);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($node) {
            $node->insertBefore($node, null);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($node, $document) {
            $node->insertBefore($node, $document->createTextNode('child'));
        }, HierarchyRequestError::class);
    }

    public function test2()
    {
        $document = $this->getHTMLDocument();

        $this->assertThrows(function () use ($document) {
            $document->body->insertBefore(
                $document->body,
                $document->getElementById('log')
            );
        }, HierarchyRequestError::class);

        $this->assertThrows(function () use ($document) {
            $document->body->insertBefore(
                $document->documentElement,
                $document->getElementById('log')
            );
        }, HierarchyRequestError::class);
    }

    /**
     * Calling insertBefore with a reference child whose parent is not the
     * context node must throw a NotFoundError.
     */
    public function test3()
    {
        $this->expectException(NotFoundError::class);
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');
        $b = $document->createElement('div');
        $c = $document->createElement('div');
        $a->insertBefore($b, $c);
    }

    /**
     * If the context node is a document, inserting a document or text node
     * should throw a HierarchyRequestError.
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $doc2 = $document->implementation->createHTMLDocument('title');

        $this->assertThrows(function () use ($doc, $doc2) {
            $doc->insertBefore($doc2, $doc->documentElement);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc) {
            $doc->insertBefore(
                $doc->createTextNode('text'),
                $doc->documentElement
            );
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, appending a DocumentFragment that
     * contains a text node or too many elements should throw a
     * HierarchyRequestError.
     */
    public function test5()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $df->appendChild($doc->createElement('b'));

        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, null);
        }, HierarchyRequestError::class);

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createTextNode('text'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, null);
        }, HierarchyRequestError::class);

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createComment('comment'));
        $df->appendChild($doc->createTextNode('text'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, null);
        }, HierarchyRequestError::class);
    }

    // Step 4.1
    public function test51(): void
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $doc2 = $document->implementation->createHTMLDocument('title2');

        $this->assertThrows(static function () use ($doc, $doc2): void {
            $doc->insertBefore($doc2, $doc->documentElement);
        }, HierarchyRequestError::class);
        $this->assertThrows(static function () use ($doc): void {
            $doc->insertBefore($doc->createTextNode('text'), $doc->documentElement);
        }, HierarchyRequestError::class);
    }

    /**
     * // Step 4.2.1.
     *
     * If the context node is a document, inserting a DocumentFragment that
     * contains a text node or too many elements should throw a
     * HierarchyRequestError.
     */
    public function test6()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $doc->removeChild($doc->documentElement);

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $df->appendChild($doc->createElement('b'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, $doc->firstChild);
        }, HierarchyRequestError::class);

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createTextNode('text'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, $doc->firstChild);
        }, HierarchyRequestError::class);

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createComment('comment'));
        $df->appendChild($doc->createTextNode('text'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, $doc->firstChild);
        }, HierarchyRequestError::class);
    }

    /**
     * // Step 4.2.2
     *
     * If the context node is a document, inserting a DocumentFragment with an
     * element if there already is an element child should throw a
     * HierarchyRequestError.
     */
    public function test7()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->appendChild($doc->createComment('foo'));
        $this->assertEquals([$doc->doctype, $doc->documentElement, $comment], iterator_to_array($doc->childNodes));

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, $doc->doctype);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, $doc->documentElement);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $df, $comment) {
            $doc->insertBefore($df, $comment);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $df) {
            $doc->insertBefore($df, null);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document and a doctype is following the reference child, inserting
     * a DocumentFragment with an element should throw a HierarchyRequestError.
     */
    public function test75(): void
    {
        // /child/ is a doctype.
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->insertBefore($doc->createComment('foo'), $doc->firstChild);
        $doc->removeChild($doc->documentElement);

        $this->assertSame([$comment, $doc->doctype], iterator_to_array($doc->childNodes));

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));

        $this->assertThrows(static function () use ($df, $doc): void {
            $doc->insertBefore($df, $doc->doctype);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document and a doctype is following the
     * reference child, inserting a DocumentFragment with an element should
     * throw a HierarchyRequestError.
     */
    public function test8()
    {
        // /child/ is not null and a doctype is following /child/.
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

        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $this->assertThrows(function () use ($doc, $df, $comment) {
            $doc->insertBefore($df, $comment);
        }, HierarchyRequestError::class);
    }

    /**
     * Step 4.3
     *
     * If the context node is a document, inserting an element if there already
     * is an element child should throw a HierarchyRequestError.
     */
    public function test9()
    {
        // The context node has an element child.
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');
        $comment = $doc->appendChild($doc->createComment('foo'));
        $this->assertSame(
            [$doc->doctype, $doc->documentElement, $comment],
            iterator_to_array($doc->childNodes)
        );

        $a = $doc->createElement('a');
        $this->assertThrows(function () use ($doc, $a) {
            $doc->insertBefore($a, $doc->doctype);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $a) {
            $doc->insertBefore($a, $doc->documentElement);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $a, $comment) {
            $doc->insertBefore($a, $comment);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $a) {
            $doc->insertBefore($a, null);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document, inserting an element before the
     * doctype should throw a HierarchyRequestError.
     */
    public function test10()
    {
        // /child/ is a doctype.
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
        $this->assertThrows(function () use ($doc, $a) {
            $doc->insertBefore($a, $doc->doctype);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document and a doctype is following the
     * reference child, inserting an element should throw a HierarchyRequestError.
     */
    public function test11()
    {
        // /child/ is not null and a doctype is following /child/.
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
            $doc->insertBefore($a, $comment);
        }, HierarchyRequestError::class);
    }

    /**
     * Step 4.4
     *
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
            $doc->insertBefore($doctype, $comment);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $doctype) {
            $doc->insertBefore($doctype, $doc->doctype);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $doctype) {
            $doc->insertBefore($doctype, $doc->documentElement);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $doctype) {
            $doc->insertBefore($doctype, null);
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
            $doc->insertBefore($doctype, $comment);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is a document with and element child, appending a
     * doctype should throw a HierarchyRequestError.
     */
    public function test14()
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
        $this->assertThrows(function () use ($doc, $doctype) {
            $doc->insertBefore($doctype, null);
        }, HierarchyRequestError::class);
    }

    /**
     * Step 5
     *
     * If the context node is a DocumentFragment, inserting a document or a
     * doctype should throw a HierarchyRequestError.
     */
    public function test15()
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $a = $df->appendChild($document->createElement('a'));

        $doc = $document->implementation->createHTMLDocument('title');
        $this->assertThrows(function () use ($doc, $df, $a) {
            $df->insertBefore($doc, $a);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $df) {
            $df->insertBefore($doc, null);
        }, HierarchyRequestError::class);

        $doctype = $document->implementation->createDocumentType('html', '', '');
        $this->assertThrows(function () use ($doctype, $df, $a) {
            $df->insertBefore($doctype, $a);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doctype, $df) {
            $df->insertBefore($doctype, null);
        }, HierarchyRequestError::class);
    }

    /**
     * If the context node is an element, inserting a document or a doctype
     * should throw a HierarchyRequestError.
     */
    public function test16()
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $a = $el->appendChild($document->createElement('a'));

        $doc = $document->implementation->createHTMLDocument('title');
        $this->assertThrows(function () use ($doc, $el, $a) {
            $el->insertBefore($doc, $a);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $el) {
            $el->insertBefore($doc, null);
        }, HierarchyRequestError::class);

        $doctype = $document->implementation->createDocumentType('html', '', '');
        $this->assertThrows(function () use ($doctype, $el, $a) {
            $el->insertBefore($doctype, $a);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc, $el) {
            $el->insertBefore($doc, null);
        }, HierarchyRequestError::class);
    }

    /**
     * Step 7
     *
     * Inserting a node before itself should not move the node
     */
    public function test17()
    {
        $document = $this->getHTMLDocument();
        $a = $document->createElement('div');
        $b = $document->createElement('div');
        $c = $document->createElement('div');
        $a->appendChild($b);
        $a->appendChild($c);
        $this->assertSame([$b, $c], iterator_to_array($a->childNodes));
        $this->assertSame($b, $a->insertBefore($b, $b));
        $this->assertSame([$b, $c], iterator_to_array($a->childNodes));
        $this->assertSame($c, $a->insertBefore($c, $c));
        $this->assertSame([$b, $c], iterator_to_array($a->childNodes));
    }

    public static function getDocumentName(): string
    {
        return 'Node-insertBefore.html';
    }

    public function getMethodName(): string
    {
        return 'insertBefore';
    }
}
