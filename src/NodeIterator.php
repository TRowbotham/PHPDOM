<?php
namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#nodeiterator
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeIterator
 */
final class NodeIterator
{
    use NodeFilterUtils;

    private $filter;
    private $pointerBeforeReferenceNode;
    private $referenceNode;
    private $root;
    private $whatToShow;

    public function __construct(
        Node $root,
        $whatToShow = NodeFilter::SHOW_ALL,
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

    public function __get($name)
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

    public function nextNode()
    {
        return $this->traverse('next');
    }

    public function previousNode()
    {
        return $this->traverse('previous');
    }

    public function detatch()
    {
    }

    public function _preremove($aNodeToBeRemoved)
    {
        if (!$aNodeToBeRemoved->contains($this->referenceNode)) {
            return;
        }

        if ($this->pointerBeforeReferenceNode) {
            $iter = new self(
                $this->root,
                NodeFilter::SHOW_ALL,
                function ($aNode) use ($aNodeToBeRemoved) {
                    return !$aNode->isInclusiveDescendantOf($aNodeToBeRemoved)
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

        $node = $aNodeToBeRemoved->previousSibling;

        if (!$node) {
            $this->referenceNode = $aNodeToBeRemoved->parentNode;
            return;
        }

        $iter = new self($node);

        while ($temp = $iter->nextNode()) {
            $this->referenceNode = $temp;
        }
    }

    private function traverse($aDirection)
    {
        $node = $this->referenceNode;
        $beforeNode = $this->pointerBeforeReferenceNode;

        while (true) {
            switch ($aDirection) {
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
                        } while($temp);

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

                        if (
                            $this->referenceNode === $this->root ||
                            !($node = $node->parentNode)
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
