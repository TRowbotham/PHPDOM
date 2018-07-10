<?php
declare(strict_types=1);

namespace Rowbot\DOM;

use function is_callable;

/**
 * @see https://dom.spec.whatwg.org/#nodeiterator
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeIterator
 *
 * @property-read \Rowbot\DOM\Node                     $root
 * @property-read \Rowbot\DOM\Node                     $referenceNode
 * @property-read bool                                 $pointerBeforeReferenceNode
 * @property-read int                                  $whatToShow
 * @property-read \Rowbot\DOM\NodeFilter|callable|null $filter
 */
final class NodeIterator
{
    use NodeFilterUtils;

    /**
     * @var \Rowbot\DOM\NodeFilter|callable|null
     */
    private $filter;

    /**
     * @var bool
     */
    private $pointerBeforeReferenceNode;

    /**
     * @var \Rowbot\DOM\Node
     */
    private $referenceNode;

    /**
     * @var \Rowbot\DOM\Node
     */
    private $root;

    /**
     * @var int
     */
    private $whatToShow;

    /**
     * Constructor.
     *
     * @param \Rowbot\DOM\Node                     $root
     * @param int                                  $whatToShow
     * @param \Rowbot\DOM\NodeFilter|callable|null $filter
     *
     * @return void
     */
    public function __construct(
        Node $root,
        int $whatToShow = NodeFilter::SHOW_ALL,
        $filter = null
    ) {
        $this->filter = null;

        if ($filter instanceof NodeFilter || is_callable($filter)) {
            $this->filter = $filter;
        }

        $this->pointerBeforeReferenceNode = true;
        $this->referenceNode = $root;
        $this->root = $root;
        $this->whatToShow = $whatToShow;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'filter':
                return $this->filter;

            case 'pointerBeforeReferenceNode':
                return $this->pointerBeforeReferenceNode;

            case 'referenceNode':
                return $this->referenceNode;

            case 'root':
                return $this->root;

            case 'whatToShow':
                return $this->whatToShow;
        }
    }

    /**
     * Returns the next node in the iterator.
     *
     * @see https://dom.spec.whatwg.org/#dom-nodeiterator-nextnode
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function nextNode(): ?Node
    {
        return $this->traverse('next');
    }

    /**
     * Returns the previous node in the iterator.
     *
     * @see https://dom.spec.whatwg.org/#dom-nodeiterator-previousnode
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function previousNode(): ?Node
    {
        return $this->traverse('previous');
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#nodeiterator-pre-removing-steps
     *
     * @param \Rowbot\DOM\Node $nodeToBeRemoved
     *
     * @return void
     */
    public function preremoveNode(Node $nodeToBeRemoved): void
    {
        if (!$nodeToBeRemoved->contains($this->referenceNode)) {
            return;
        }

        if ($this->pointerBeforeReferenceNode) {
            $iter = new self(
                $this->root,
                NodeFilter::SHOW_ALL,
                function ($aNode) use ($nodeToBeRemoved) {
                    return !$aNode->isInclusiveDescendantOf($nodeToBeRemoved)
                        ? NodeFilter::FILTER_ACCEPT
                        : NodeFilter::FILTER_REJECT;
                }
            );
            $next = $iter->nextNode();

            if ($next) {
                $this->referenceNode = $next;
                return;
            }

            $this->pointerBeforeReferenceNode = false;
        }

        $node = $nodeToBeRemoved->previousSibling;

        if (!$node) {
            $this->referenceNode = $nodeToBeRemoved->parentNode;
            return;
        }

        $iter = new self($node);

        while ($temp = $iter->nextNode()) {
            $this->referenceNode = $temp;
        }
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-nodeiterator-traverse
     *
     * @param string $direction
     *
     * @return \Rowbot\DOM\Node|null
     */
    private function traverse(string $direction): ?Node
    {
        $node = $this->referenceNode;
        $beforeNode = $this->pointerBeforeReferenceNode;

        while (true) {
            switch ($direction) {
                case 'next':
                    if (!$beforeNode) {
                        $firstChild = $node->firstChild;

                        if ($firstChild) {
                            $node = $firstChild;
                            break;
                        }

                        $sibling = null;
                        $temp = $node;

                        do {
                            if ($temp === $this->root) {
                                break;
                            }

                            $sibling = $temp->nextSibling;

                            if ($sibling) {
                                break;
                            }

                            $temp = $temp->parentNode;
                        } while ($temp);

                        $node = $sibling;

                        if (!$sibling) {
                            return null;
                        }
                    } else {
                        $beforeNode = false;
                    }

                    break;

                case 'previous':
                    if ($beforeNode) {
                        $sibling = $node->previousSibling;

                        if ($sibling) {
                            $node = $sibling;

                            while (($lastChild = $node->lastChild)) {
                                $node = $lastChild;
                            }
                        }

                        if ($this->referenceNode === $this->root
                            || !($node = $node->parentNode)
                        ) {
                            return null;
                        }
                    } else {
                        $beforeNode = true;
                    }
            }

            $result = $this->filterNode($node);

            if ($result == NodeFilter::FILTER_ACCEPT) {
                break;
            }
        }

        $this->referenceNode = $node;
        $this->pointerBeforeReferenceNode = $beforeNode;

        return $node;
    }
}
