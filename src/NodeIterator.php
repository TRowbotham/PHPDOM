<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#nodeiterator
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeIterator
 */
final class NodeIterator extends NodeTraverser
{
    public Node $root {
        get => $this->context->root;
    }

    public Node $referenceNode {
        get => $this->context->referenceNode;
    }

    public bool $pointerBeforeReferenceNode {
        get => $this->context->pointerBeforeReferenceNode;
    }

    private NodeIteratorContext $context;

    /**
     * @param \Rowbot\DOM\NodeFilter::SHOW_*       $whatToShow
     * @param \Rowbot\DOM\NodeFilter|callable|null $filter
     */
    public function __construct(Node $root, int $whatToShow = NodeFilter::SHOW_ALL, mixed $filter = null)
    {
        parent::__construct($whatToShow, $filter);

        $this->context = new NodeIteratorContext($root);
        $this->context->observeSelf();
    }

    public function __destruct()
    {
        $this->context->unobserveSelf();
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
     * @see https://dom.spec.whatwg.org/#concept-nodeiterator-traverse
     */
    private function traverse(string $direction): ?Node
    {
        $node = $this->context->referenceNode;
        $beforeNode = $this->context->pointerBeforeReferenceNode;

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

                        if (!$sibling) {
                            return null;
                        }

                        $node = $sibling;
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

                        if ($this->context->referenceNode === $this->root || !($node = $node->parentNode)) {
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

        $this->context->referenceNode = $node;
        $this->context->pointerBeforeReferenceNode = $beforeNode;

        return $node;
    }
}
