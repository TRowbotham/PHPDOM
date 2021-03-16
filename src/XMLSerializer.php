<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Parser\XML\FragmentSerializer;

/**
 * @see https://w3c.github.io/DOM-Parsing/#the-xmlserializer-interface
 */
final class XMLSerializer
{
    /**
     * Serializes the node as an XML string.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dfn-serializetostring
     */
    public function serializeToString(Node $root): string
    {
        return (new FragmentSerializer(false))->serializeFragment($root, false);
    }
}
