<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use function count;

trait ChildOrParentNode
{
    /**
     * Converts an array of Nodes and strings and creates a single node,
     * such as a DocumentFragment. Any strings contained in the array will be
     * turned in to Text nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#converting-nodes-into-a-node
     *
     * @param list<\Rowbot\DOM\Node|string> $potentialNodes
     *
     * @return \Rowbot\DOM\DocumentFragment|\Rowbot\DOM\Node If $nodes > 1, then a DocumentFragment is returned,
     *                                                       otherwise a single Node is returned.
     */
    private function convertNodesToNode(array $potentialNodes, Document $document): Node
    {
        $node = null;
        $nodes = [];

        // Replace each string in nodes with a new Text node whose data is the
        // string and node document is document.
        foreach ($potentialNodes as $potentialNode) {
            if (!$potentialNode instanceof Node) {
                $nodes[] = new Text($document, (string) $potentialNode);

                continue;
            }

            $nodes[] = $potentialNode;
        }

        // If nodes contains one node, set node to that node. Otherwise, set
        // node to a new DocumentFragment whose node document is document, and
        // then append each node in nodes, if any, to it. Rethrow any
        // exceptions.
        if (count($nodes) === 1) {
            return $nodes[0];
        }

        $node = new DocumentFragment($document);

        foreach ($nodes as $child) {
            $node->appendChild($child);
        }

        return $node;
    }
}
