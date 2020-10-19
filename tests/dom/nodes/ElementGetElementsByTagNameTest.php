<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Node;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-getElementsByTagName.html
 */
class ElementGetElementsByTagNameTest extends NodeTestCase
{
    use DocumentElementGetElementsByTagNameTrait;

    public static function runSetup(): Element
    {
        static $element = null;

        if ($element) {
            return $element;
        }

        $document = self::getWindow()->document;
        $element = $document->createElement('div');
        $element->appendChild($document->createTextNode('text'));
        $p = $element->appendChild($document->createElement('p'));
        $p->appendChild($document->createElement('a'))
            ->appendChild($document->createElement('link'));
        $p->appendChild($document->createElement('b'))
            ->appendChild($document->createElement('bold'));
        $p->appendChild($document->createElement('em'))
            ->appendChild($document->createElement('u'))
            ->appendChild($document->createTextNode('emphasized'));
        $element->appendChild($document->createComment('comment'));

        return $element;
    }

    public static function context(): Node
    {
        return self::runSetup();
    }

    public static function element(): Element
    {
        return self::runSetup();
    }

    public static function getDocumentName(): string
    {
        return 'Element-getElementsByTagName.html';
    }
}
