<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-lookupNamespaceURI.html
 */
class NodeLookupNamespaceURITest extends TestCase
{
    use DocumentGetter;

    public function test1()
    {
        $frag = $this->getHTMLDocument()->createDocumentFragment();
        $this->assertSame(
            null,
            $frag->lookupNamespaceURI(null),
            'DocumentFragment should have null namespace, prefix null'
        );
        $this->assertSame(
            null,
            $frag->lookupNamespaceURI(''),
            'DocumentFragment should have null namespace, prefix ""'
        );
        $this->assertSame(
            null,
            $frag->lookupNamespaceURI('foo'),
            'DocumentFragment should have null namespace, prefix "foo"'
        );
        $this->assertSame(
            null,
            $frag->lookupNamespaceURI('xmlns'),
            'DocumentFragment should have null namespace, prefix "xmlns"'
        );
        $this->assertSame(
            true,
            $frag->isDefaultNamespace(null),
            'DocumentFragment is in default namespace, prefix null'
        );
        $this->assertSame(
            true,
            $frag->isDefaultNamespace(''),
            'DocumentFragment is in default namespace, prefix ""'
        );
        $this->assertSame(
            false,
            $frag->isDefaultNamespace('foo'),
            'DocumentFragment is in default namespace, prefix "foo"'
        );
        $this->assertSame(
            false,
            $frag->isDefaultNamespace('xmlns'),
            'DocumentFragment is in default namespace, prefix "xmlns"'
        );
    }

    public function test2()
    {
        $document = $this->getHTMLDocument();
        $fooElem = $document->createElementNS(
            'fooNamespace',
            'prefix:elem'
        );
        $fooElem->setAttribute('bar', 'value');

        $this->assertSame(
            null,
            $fooElem->lookupNamespaceURI(null),
            'Element should have null namespace, prefix null'
        );
        $this->assertSame(
            null,
            $fooElem->lookupNamespaceURI(''),
            'Element should have null namespace, prefix ""'
        );
        $this->assertSame(
            null,
            $fooElem->lookupNamespaceURI('fooNamespace'),
            'Element should not have namespace matching prefix with namespaceURI value'
        );
        $this->assertSame(
            null,
            $fooElem->lookupNamespaceURI('xmlns'),
            'Element should not have XMLNS namespace'
        );
        $this->assertSame(
            'fooNamespace',
            $fooElem->lookupNamespaceURI('prefix'),
            'Element has namespace URI matching prefix'
        );

        $this->assertSame(
            true,
            $fooElem->isDefaultNamespace(null),
            'Empty namespace is not default, prefix null'
        );
        $this->assertSame(
            true,
            $fooElem->isDefaultNamespace(''),
            'Empty namespace is not default, prefix ""'
        );
        $this->assertSame(
            false,
            $fooElem->isDefaultNamespace('fooNamespace'),
            'fooNamespace is not default'
        );
        $this->assertSame(
            false,
            $fooElem->isDefaultNamespace('http://www.w3.org/2000/xmlns/'),
            'xmlns namespace is not default'
        );

        $fooElem->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:bar',
            'barURI'
        );
        $fooElem->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns',
            'bazURI'
        );

        $this->assertSame(
            'bazURI',
            $fooElem->lookupNamespaceURI(null),
            'Element should have baz namespace, prefix null'
        );
        $this->assertSame(
            'bazURI',
            $fooElem->lookupNamespaceURI(''),
            'Element should have baz namespace, prefix ""'
        );
        $this->assertSame(
            null,
            $fooElem->lookupNamespaceURI('xmlns'),
            'Element does not has namespace with xlmns prefix'
        );
        $this->assertSame(
            'barURI',
            $fooElem->lookupNamespaceURI('bar'),
            'Element has bar namespace'
        );

        $this->assertSame(
            false,
            $fooElem->isDefaultNamespace(null),
            'Empty namespace is not default on fooElem, prefix null'
        );
        $this->assertSame(
            false,
            $fooElem->isDefaultNamespace(''),
            'Empty namespace is not default on fooElem, prefix ""'
        );
        $this->assertSame(
            false,
            $fooElem->isDefaultNamespace('barURI'),
            'bar namespace is not default'
        );
        $this->assertSame(
            true,
            $fooElem->isDefaultNamespace('bazURI'),
            'baz namespace is default'
        );

        $comment = $document->createComment('comment');
        $fooElem->appendChild($comment);

        $this->assertSame(
            'bazURI',
            $comment->lookupNamespaceURI(null),
            'Comment should inherit baz namespace'
        );
        $this->assertSame(
            'bazURI',
            $comment->lookupNamespaceURI(''),
            'Comment should inherit  baz namespace'
        );
        $this->assertSame(
            'fooNamespace',
            $comment->lookupNamespaceURI('prefix'),
            'Comment should inherit namespace URI matching prefix'
        );
        $this->assertSame(
            'barURI',
            $comment->lookupNamespaceURI('bar'),
            'Comment should inherit bar namespace'
        );

        $this->assertSame(
            false,
            $comment->isDefaultNamespace(null),
            'For comment, empty namespace is not default, prefix null'
        );
        $this->assertSame(
            false,
            $comment->isDefaultNamespace(''),
            'For comment, empty namespace is not default, prefix ""'
        );
        $this->assertSame(
            false,
            $comment->isDefaultNamespace('fooNamespace'),
            'For comment, fooNamespace is not default'
        );
        $this->assertSame(
            false,
            $comment->isDefaultNamespace('http://www.w3.org/2000/xmlns/'),
            'For comment, xmlns namespace is not default'
        );
        $this->assertSame(
            false,
            $comment->isDefaultNamespace('barURI'),
            'For comment, inherited bar namespace is not default'
        );
        $this->assertSame(
            true,
            $comment->isDefaultNamespace('bazURI'),
            'For comment, inherited baz namespace is default'
        );

        $fooChild = $document->createElementNS(
            'childNamespace',
            'childElem'
        );
        $fooElem->appendChild($fooChild);

        $this->assertSame(
            'childNamespace',
            $fooChild->lookupNamespaceURI(null),
            'Child element should inherit baz namespace'
        );
        $this->assertSame(
            'childNamespace',
            $fooChild->lookupNamespaceURI(''),
            'Child element should have null namespace'
        );
        $this->assertSame(
            null,
            $fooChild->lookupNamespaceURI('xmlns'),
            'Child element should not have XMLNS namespace'
        );
        $this->assertSame(
            'fooNamespace',
            $fooChild->lookupNamespaceURI('prefix'),
            'Child element has namespace URI matching prefix'
        );

        $this->assertSame(
            false,
            $fooChild->isDefaultNamespace(null),
            'Empty namespace is not default for child, prefix null'
        );
        $this->assertSame(
            false,
            $fooChild->isDefaultNamespace(''),
            'Empty namespace is not default for child, prefix ""'
        );
        $this->assertSame(
            false,
            $fooChild->isDefaultNamespace('fooNamespace'),
            'fooNamespace is not default for child'
        );
        $this->assertSame(
            false,
            $fooChild->isDefaultNamespace('http://www.w3.org/2000/xmlns/'),
            'xmlns namespace is not default for child'
        );
        $this->assertSame(
            false,
            $fooChild->isDefaultNamespace('barURI'),
            'bar namespace is not default for child'
        );
        $this->assertSame(
            false,
            $fooChild->isDefaultNamespace('bazURI'),
            'baz namespace is default for child'
        );
        $this->assertSame(
            true,
            $fooChild->isDefaultNamespace('childNamespace'),
            'childNamespace is default for child'
        );

        $document->documentElement->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:bar',
            'barURI'
        );
        $document->documentElement->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns',
            'bazURI'
        );

        $this->assertSame(
            'http://www.w3.org/1999/xhtml',
            $document->lookupNamespaceURI(null),
            'Document should have xhtml namespace, prefix null'
        );
        $this->assertSame(
            'http://www.w3.org/1999/xhtml',
            $document->lookupNamespaceURI(''),
            'Document should have xhtml namespace, prefix ""'
        );
        $this->assertSame(
            null,
            $document->lookupNamespaceURI('prefix'),
            'Document has no namespace URI matching prefix'
        );
        $this->assertSame(
            'barURI',
            $document->lookupNamespaceURI('bar'),
            'Document has bar namespace'
        );

        $this->assertSame(
            false,
            $document->isDefaultNamespace(null),
            'For document, empty namespace is not default, prefix null'
        );
        $this->assertSame(
            false,
            $document->isDefaultNamespace(''),
            'For document, empty namespace is not default, prefix ""'
        );
        $this->assertSame(
            false,
            $document->isDefaultNamespace('fooNamespace'),
            'For document, fooNamespace is not default'
        );
        $this->assertSame(
            false,
            $document->isDefaultNamespace('http://www.w3.org/2000/xmlns/'),
            'For document, xmlns namespace is not default'
        );
        $this->assertSame(
            false,
            $document->isDefaultNamespace('barURI'),
            'For document, bar namespace is not default'
        );
        $this->assertSame(
            false,
            $document->isDefaultNamespace('bazURI'),
            'For document, baz namespace is not default'
        );
        $this->assertSame(
            true,
            $document->isDefaultNamespace('http://www.w3.org/1999/xhtml'),
            'For document, xhtml namespace is default'
        );

        $comment = $document->createComment('comment');
        $document->appendChild($comment);
        $this->assertSame(
            null,
            $comment->lookupNamespaceURI('bar'),
            'Comment does not have bar namespace'
        );
    }
}
