<?php

declare(strict_types=1);

namespace Rowbot\DOM;

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
     * @param \Rowbot\DOM\NodeFilter|callable|null $filter
     */
    public function __construct(Node $root, int $whatToShow = NodeFilter::SHOW_ALL, $filter = null)
    {
        $this->setFilter($filter);
        $this->pointerBeforeReferenceNode = true;
        $this->referenceNode = $root;
        $this->root = $root;
        $this->whatToShow = $whatToShow;
    }

    /**
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
     */
    public function nextNode(): ?Node
    {
        return $this->traverse('next');
    }

    /**
     * Returns the previous node in the iterator.
     *
     * @see https://dom.spec.whatwg.org/#dom-nodeiterator-previousnode
     */
    public function previousNode(): ?Node
    {
        return $this->traverse('previous');
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#nodeiterator-pre-removing-steps
     */
    public function preremoveNode(Node $nodeToBeRemoved): void
    {
        if (!$nodeToBeRemoved->contains($this->referenceNode) || $nodeToBeRemoved === $this->root) {
            return;
        }

        if ($this->pointerBeforeReferenceNode) {
            $next = null;
            $node = $nodeToBeRemoved;

            while ($node && !$node->nextSibling) {
                if ($node === $this->root) {
                    break;
                }

                $node = $node->parentNode;
            }

            if ($node && $node !== $this->root) {
                $next = $node->nextSibling;
            }

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

                        if ($this->referenceNode === $this->root || !($node = $node->parentNode)) {
                            return null;
                        }
                    } else {
                        $beforeNode = true;
                    }
            }

            $result = $this->filterNode($node);

            if ($result === NodeFilter::FILTER_ACCEPT) {
                break;
            }
        }

        $this->referenceNode = $node;
        $this->pointerBeforeReferenceNode = $beforeNode;

        return $node;
    }
}
