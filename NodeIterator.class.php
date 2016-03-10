<?php
namespace phpjs;

/**
 * @see https://dom.spec.whatwg.org/#nodeiterator
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeIterator
 */
final class NodeIterator
{
    private $mFilter;
    private $mPointerBeforeReferenceNode;
    private $mReferenceNode;
    private $mRoot;
    private $mWhatToShow;

    public function __construct(
        Node $aRoot,
        $aWhatToShow = NodeFilter::SHOW_ALL,
        callable $aFilter = null
    ) {
        $this->mFilter = $aFilter;
        $this->mPointerBeforeReferenceNode = true;
        $this->mReferenceNode = $aRoot;
        $this->mRoot = $aRoot;
        $this->mWhatToShow = $aWhatToShow;
    }

    public function __destruct()
    {
        $this->mFilter = null;
        $this->mReferenceNode = null;
        $this->mRoot = null;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'filter':
                return $this->mFilter;

            case 'pointerBeforeReferenceNode':
                return $this->mPointerBeforeReferenceNode;

            case 'referenceNode':
                return $this->mReferenceNode;

            case 'root':
                return $this->mRoot;

            case 'whatToShow':
                return $this->mWhatToShow;
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
        if (!$aNodeToBeRemoved->contains($this->mReferenceNode)) {
            return;
        }

        if ($this->mPointerBeforeReferenceNode) {
            $root = $this->mRoot;
            $filter = function ($aNode) use ($root, $aNodeToBeRemoved) {
                return $root->contains($aNode) &&
                    !$aNodeToBeRemoved->contains($aNode) ?
                        NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_REJECT;
            };
            $iter = new TreeWalker($this->mRoot, NodeFilter::SHOW_ALL, $filter);
            $iter->currentNode = $aNodeToBeRemoved;
            $next = $iter->nextNode();

            if ($next) {
                $this->mReferenceNode = $next;
                return;
            }

            $this->mPointerBeforeReferenceNode = false;
        }

        $node = $aNodeToBeRemoved->previousSibling;

        if ($node) {
            while (($lastChild = $node->lastChild)) {
                $node = $lastChild;
            }
        } else {
            $node = $aNodeToBeRemoved->parentNode;
        }

        $this->mReferenceNode = $node;
    }

    private function traverse($aDirection)
    {
        $node = $this->mReferenceNode;
        $beforeNode = $this->mPointerBeforeReferenceNode;

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
                            if ($temp === $this->mRoot) {
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
                            $this->mReferenceNode === $this->mRoot ||
                            !($node = $node->parentNode)
                        ) {
                            return null;
                        }
                    } else {
                        $beforeNode = true;
                    }
            }

            $result = NodeFilter::_filter($node, $this);

            if ($result == NodeFilter::FILTER_ACCEPT) {
                break;
            }
        }

        $this->mReferenceNode = $node;
        $this->mPointerBeforeReferenceNode = $beforeNode;

        return $node;
    }
}
