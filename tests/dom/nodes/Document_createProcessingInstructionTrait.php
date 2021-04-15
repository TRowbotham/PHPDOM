<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Document;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Node;
use Rowbot\DOM\ProcessingInstruction;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createProcessingInstruction.js
 */
trait Document_createProcessingInstructionTrait
{
    /**
     * @dataProvider invalidNamesProvider
     */
    public function testCreateProcessingInstructionInvalidNames($target, string $data): void
    {
        $this->expectException(InvalidCharacterError::class);
        $this->getDocument()->createProcessingInstruction((string) $target, $data);
    }

    /**
     * @dataProvider validNamesProvider
     */
    public function testCreateProcessingInstructionValidNames($target, string $data): void
    {
        $document = $this->getDocument();
        $pi = $document->createProcessingInstruction($target, $data);

        $this->assertSame($target, $pi->target);
        $this->assertSame($data, $pi->data);
        $this->assertSame($document, $pi->ownerDocument);
        $this->assertInstanceOf(ProcessingInstruction::class, $pi);
        $this->assertInstanceOf(Node::class, $pi);
    }

    public function invalidNamesProvider(): array
    {
        return [
            ["A", "?>"],
            ["\u{00B7}A", "x"],
            ["\u{00D7}A", "x"],
            ["A\u{00D7}", "x"],
            ["\\A", "x"],
            ["\f", "x"],
            [0, "x"],
            ["0", "x"],
        ];
    }

    public function validNamesProvider(): array
    {
        return [
            ["xml:fail", "x"],
            ["A\u{00B7A}", "x"],
            ["a0", "x"],
        ];
    }

    abstract public function getDocument(): Document;
}
