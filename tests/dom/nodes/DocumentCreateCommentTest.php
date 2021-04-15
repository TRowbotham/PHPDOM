<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\Comment;
use Rowbot\DOM\HTMLDocument;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createComment.html
 */
class DocumentCreateCommentTest extends CharacterDataTestCase
{
    /**
     * @dataProvider commentNodeDataProvider
     */
    public function testCreateComment(
        string $method,
        string $iface,
        int $nodeType,
        string $nodeValue,
        $value
    ): void {
        $this->checkDocumentCreateMethod(new HTMLDocument(), $method, $iface, $nodeType, $nodeValue, $value);
    }

    public function commentNodeDataProvider(): Generator
    {
        foreach ($this->valuesProvider() as $value) {
            yield ['createComment', Comment::class, 8, '#comment', $value];
        }
    }
}
