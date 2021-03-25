<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\CharacterData;
use Rowbot\DOM\Document;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase;

use function strlen;

abstract class CharacterDataTestCase extends TestCase
{
    public function checkDocumentCreateMethod(
        Document $document,
        string $method,
        string $iface,
        int $nodeType,
        string $nodeName,
        $value
    ): void {
        $c = $document->{$method}($value);
        $expected = (string) $value;

        $this->assertInstanceOf($iface, $c);
        $this->assertInstanceOf(CharacterData::class, $c);
        $this->assertInstanceOf(Node::class, $c);
        $this->assertSame($document, $c->ownerDocument);
        $this->assertSame($expected, $c->data);
        $this->assertSame($expected, $c->nodeValue);
        $this->assertSame($expected, $c->textContent);
        $this->assertSame(strlen($expected), $c->length);
        $this->assertSame($nodeType, $c->nodeType);
        $this->assertSame($nodeName, $c->nodeName);
        $this->assertFalse($c->hasChildNodes());
        $this->assertSame(0, $c->childNodes->length);
        $this->assertNull($c->firstChild);
        $this->assertNull($c->lastChild);
    }

    public function valuesProvider(): array
    {
        return ["\u{000b}", "a -- b", "a-", "-b", /* null, undefined */];
    }
}
