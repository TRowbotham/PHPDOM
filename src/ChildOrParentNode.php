<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Exception\DOMException;

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
     * @param (\Rowbot\DOM\Node|string)[] $nodes    An array of Nodes and strings.
     * @param \Rowbot\DOM\Document        $document Context object's node document.
     *
     * @return \Rowbot\DOM\DocumentFragment|\Rowbot\DOM\Node If $nodes > 1, then a DocumentFragment is returned,
     *                                                       otherwise a single Node is returned.
     */
    private function convertNodesToNode($nodes, Document $document)
    {
        $node = null;

        // Replace each string in nodes with a new Text node whose data is the
        // string and node document is document.
        foreach ($nodes as &$potentialNode) {
            if (!$potentialNode instanceof Node) {
                $potentialNode = new Text(Utils::DOMString($potentialNode));
                $potentialNode->setNodeDocument($document);
            }
        }

        unset($potentialNode);

        // If nodes contains one node, set node to that node. Otherwise, set
        // node to a new DocumentFragment whose node document is document, and
        // then append each node in nodes, if any, to it. Rethrow any
        // exceptions.
        if (count($nodes) == 1) {
            return $nodes[0];
        }

        $node = new DocumentFragment();
        $node->setNodeDocument($document);

        try {
            foreach ($nodes as $child) {
                $node->appendChild($child);
            }
        } catch (DOMException $e) {
            throw $e;
        }

        return $node;
    }
}
