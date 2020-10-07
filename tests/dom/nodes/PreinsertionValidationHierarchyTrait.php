<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use ReflectionClass;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/pre-insertion-validation-hierarchy.js
 */
trait PreinsertionValidationHierarchyTrait
{
    use WindowTrait;

    abstract public function getMethodName(): string;

    private function insert(Node $parent, ?Node $node): void
    {
        $reflection = new ReflectionClass($parent);
        $method = $reflection->getMethod($this->getMethodName());

        if ($method->getNumberOfRequiredParameters() > 1) {
            // This is for insertBefore(). We can't blindly pass `null` for all methods
            // as doing so will move nodes before validation.
            $method->invoke($parent, $node, null);
        } else {
            $method->invoke($parent, $node);
        }
    }

    public function testStep2(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');

        $this->assertThrows(function () use ($doc): void {
            $this->insert($doc->body, $doc->body);
        }, HierarchyRequestError::class);
        $this->assertThrows(function () use ($doc): void {
            $this->insert($doc->body, $doc->documentElement);
        }, HierarchyRequestError::class);
    }

    public function testStep4(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');
        $doc2 = self::getWindow()->document->implementation->createHTMLDocument('title2');

        $this->assertThrows(function () use ($doc, $doc2): void {
            $this->insert($doc, $doc2);
        }, HierarchyRequestError::class);
    }

    public function testStep5InsertingTextNodeIntoDocument(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');


        $this->assertThrows(function () use ($doc): void {
            $this->insert($doc, $doc->createTextNode('text'));
        }, HierarchyRequestError::class);
    }

    public function testStep5InsertingDoctypeIntoNonDocument(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');
        $doctype = $doc->childNodes[0];

        $this->assertThrows(function () use ($doc, $doctype): void {
            $this->insert($doc->createElement('a'), $doctype);
        }, HierarchyRequestError::class);
    }

    public function testStep6DocumentFragmentIncludingMultipleElements(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');
        $doc->documentElement->remove();
        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));
        $df->appendChild($doc->createElement('b'));

        $this->assertThrows(function () use ($doc, $df): void {
            $this->insert($doc, $df);
        }, HierarchyRequestError::class);
    }

    public function testStep6DocumentFragmentHasMultipleElementsWhenDocumentAlreadyHasAnElement(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');
        $df = $doc->createDocumentFragment();
        $df->appendChild($doc->createElement('a'));

        $this->assertThrows(function () use ($doc, $df): void {
            $this->insert($doc, $df);
        }, HierarchyRequestError::class);
    }

    public function testStep6Element(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');
        $el = $doc->createElement('a');

        $this->assertThrows(function () use ($doc, $el): void {
            $this->insert($doc, $el);
        }, HierarchyRequestError::class);
    }

    public function testStep6DoctypeWhenDocumentAlreadyHasAnotherDoctype(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');
        $doctype = $doc->childNodes[0]->cloneNode();
        $doc->documentElement->remove();

        $this->assertThrows(function () use ($doc, $doctype): void {
            $this->insert($doc, $doctype);
        }, HierarchyRequestError::class);
    }

    public function testStep6DoctypeWhenDocumentHasAnElement(): void
    {
        if ($this->getMethodName() === 'prepend') {
            $this->markTestSkipped('Skip `.prepend` as this doesn\'t throw if `child` is an element');
        }

        $doc = self::getWindow()->document->implementation->createHTMLDocument('title');
        $doctype = $doc->childNodes[0]->cloneNode();
        $doc->childNodes[0]->remove();

        $this->assertThrows(function () use ($doc, $doctype): void {
            $this->insert($doc, $doctype);
        }, HierarchyRequestError::class);
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }
}
