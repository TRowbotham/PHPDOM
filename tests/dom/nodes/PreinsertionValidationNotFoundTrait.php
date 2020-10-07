<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use ReflectionClass;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * This depends on PreinsertionValidationHierarchyTrait, specifically, it uses the insert() method.
 */
trait PreinsertionValidationNotFoundTrait
{
    use WindowTrait;

    abstract public function getMethodName(): string;

    /**
     * @dataProvider nonParentNodesProvider
     */
    public function testStep1HappensBeforeStep3(Node $parent): void
    {
        $document = self::getWindow()->document;
        $child = $document->createElement('div');
        $node = $document->createElement('div');
        $this->expectException(HierarchyRequestError::class);
        $this->insertFunc($parent, $node, $child);
    }

    public function testStep2HappensBeforeStep3(): void
    {
        $document = self::getWindow()->document;
        $parent = $document->createElement('div');
        $child = $document->createElement('div');
        $node = $document->createElement('div');
        $node->appendChild($parent);

        $this->expectException(HierarchyRequestError::class);
        $this->insertFunc($parent, $node, $child);
    }

    /**
     * @dataProvider nonInsertableNodesProvider
     */
    public function testStep3HappensBeforeStep4(Node $node): void
    {
        $document = self::getWindow()->document;
        $parent = $document->createElement('div');
        $child = $document->createElement('div');

        $this->expectException(NotFoundError::class);
        $this->insertFunc($parent, $node, $child);
    }

    /**
     * @dataProvider nonDocumentParentNodesProvider
     */
    public function testStep3HappensBeforeStep5(Node $parent_): void
    {
        $document = self::getWindow()->document;
        $child = $document->createElement('div');
        $node = $document->createTextNode('');
        $parent = $document->implementation->createDocument(null, 'foo', null);

        $this->assertThrows(function () use ($parent, $node, $child): void {
            $this->insertFunc($parent, $node, $child);
        }, NotFoundError::class);

        $node = $document->implementation->createDocumentType('html', '', '');

        $this->assertThrows(function () use ($parent_, $node, $child): void {
            $this->insertFunc($parent_, $node, $child);
        }, NotFoundError::class);
    }

    public function testStep3HappensBeforeStep6(): void
    {
        $document = self::getWindow()->document;
        $child = $document->createElement('div');
        $parent = $document->implementation->createDocument(null, null, null);
        $node = $document->createDocumentFragment();
        $node->appendChild($document->createElement('div'));
        $node->appendChild($document->createElement('div'));

        $this->assertThrows(function () use ($parent, $node, $child): void {
            $this->insertFunc($parent, $node, $child);
        }, NotFoundError::class);

        $node = $document->createElement('div');
        $parent->appendChild($document->createElement('div'));
        $this->assertThrows(function () use ($parent, $node, $child): void {
            $this->insertFunc($parent, $node, $child);
        }, NotFoundError::class);

        $parent->firstChild->remove();
        $parent->appendChild($document->implementation->createDocumentType('html', '', ''));
        $node = $document->implementation->createDocumentType('html', '', '');
        $this->assertThrows(function () use ($parent, $node, $child): void {
            $this->insertFunc($parent, $node, $child);
        }, NotFoundError::class);
    }

    public function insertFunc(Node $parent, Node $node, Node $child): void
    {
        $reflection = new ReflectionClass($parent);
        $method = $reflection->getMethod($this->getMethodName());
        $method->invoke($parent, $node, $child);
    }

    public function nonParentNodesProvider(): array
    {
        $document = self::getWindow()->document;

        return [
            [$document->implementation->createDocumentType('html', '', '')],
            [$document->createTextNode('text')],
            [$document->implementation->createDocument(null, 'foo', null)->createProcessingInstruction('foo', 'bar')],
            [$document->createComment('comment')],
            [$document->implementation->createDocument(null, 'foo', null)->createCDATASection('data')],
        ];
    }

    public function nonInsertableNodesProvider(): array
    {
        $document = self::getWindow()->document;

        return [
            [$document->implementation->createHTMLDocument('title')],
        ];
    }

    public function nonDocumentParentNodesProvider(): array
    {
        $document = self::getWindow()->document;

        return [
            [$document->createElement('div')],
            [$document->createDocumentFragment()],
        ];
    }
}
