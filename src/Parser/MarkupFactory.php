<?php
namespace Rowbot\DOM\Parser;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\HTML\FragmentSerializer as HTMLFragmentSerializer;
use Rowbot\DOM\Parser\XML\FragmentSerializer as XMLFragmentSerializer;

class MarkupFactory
{
    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-fragment-serializing-algorithm
     *
     * @param Node $node
     * @param bool $requireWellFormed
     *
     * @return string
     */
    public static function serializeFragment(
        Node $node,
        $requireWellFormed
    ): string {
        if ($node->getNodeDocument() instanceof HTMLDocument) {
            $serializer = new HTMLFragmentSerializer();

            return $serializer->serializeFragment($node);
        }

        $serializer = new XMLFragmentSerializer();

        return $serializer->serializeFragment($node, $requireWellFormed);
    }
}
