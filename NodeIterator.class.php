<?php
// https://dom.spec.whatwg.org/#nodeiterator
// https://developer.mozilla.org/en-US/docs/Web/API/NodeIterator

require_once 'NodeFilter.class.php';

final class NodeIterator {
    private $mFilter;
    private $mPointerBeforeReferenceNode;
    private $mReferenceNode;
    private $mRoot;
    private $mWhatToShow;

    public function __construct(Node $aRoot, $aWhatToShow = NodeFilter::SHOW_ALL, callable $aFilter = null) {
        $this->mFilter = $aFilter;
        $this->mPointerBeforeReferenceNode = true;
        $this->mReferenceNode = $aRoot;
        $this->mRoot = $aRoot;
        $this->mWhatToShow = $aWhatToShow;
    }

    public function __get($aName) {
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

    public function nextNode() {
        return $this->traverse('next');
    }

    public function previousNode() {
        return $this->traverse('previous');
    }

    public function detatch() {

    }

    private function traverse($aDirection) {
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

                        if ($this->mReferenceNode === $this->mRoot || !($node = $node->parentNode)) {
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
