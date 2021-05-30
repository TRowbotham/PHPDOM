<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\HTML\FragmentSerializer as HTMLFragmentSerializer;
use Rowbot\DOM\Parser\XML\FragmentSerializer as XMLFragmentSerializer;

class MarkupFactory
{
    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-fragment-serializing-algorithm
     */
    public static function serializeFragment(Node $node, bool $requireWellFormed): string
    {
        if ($node->getNodeDocument()->isHTMLDocument()) {
            $serializer = new HTMLFragmentSerializer();

            return $serializer->serializeFragment($node);
        }

        $serializer = new XMLFragmentSerializer();

        return $serializer->serializeFragment($node, $requireWellFormed);
    }
}
