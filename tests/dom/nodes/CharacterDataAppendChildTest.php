<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/CharacterData-appendChild.html
 */
class CharacterDataAppendChildTest extends TestCase
{
    private static $document;

    /**
     * @dataProvider typeProvider
     */
    public function testNode(string $type1, string $type2): void
    {
        $node1 = self::create($type1);
        $node2 = self::create($type2);

        $this->expectException(HierarchyRequestError::class);
        $node1->appendChild($node2);
    }

    public static function create(string $type)
    {
        switch ($type) {
            case 'Text':
                return self::$document->createTextNode('test');

            case 'Comment':
                return self::$document->createComment('test');

            case 'ProcessingInstruction':
                return self::$document->createProcessingInstruction('target', 'test');
        }
    }

    public function typeProvider(): array
    {
        self::$document = new HTMLDocument();
        $types = ["Text", "Comment", "ProcessingInstruction"];
        $tests = [];

        foreach ($types as $type1) {
            foreach ($types as $type2) {
                $tests[] = [$type1, $type2];
            }
        }

        return $tests;
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
