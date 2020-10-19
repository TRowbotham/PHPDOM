<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Node;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-getElementsByTagName.html
 */
class DocumentGetElementsByTagNameTest extends NodeTestCase
{
    use DocumentElementGetElementsByTagNameTrait;

    public static function context(): Node
    {
        return self::getWindow()->document;
    }

    public static function element(): Element
    {
        return self::getWindow()->document->body;
    }

    public static function getDocumentName(): string
    {
        return 'Document-getElementsByTagName.html';
    }
}
