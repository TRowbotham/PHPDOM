<?php
// https://dom.spec.whatwg.org/#nodeiterator
// https://developer.mozilla.org/en-US/docs/Web/API/NodeIterator

require_once 'NodeFilter.class.php';
require_once 'TreeWalker.class.php';

final class NodeIterator {
    private $mCollection;
    private $mFilter;
    private $mPointerBeforeReferenceNode;
    private $mReferenceNode;
    private $mRoot;
    private $mWhatToShow;

    public function __construct(Node $aRoot, $aWhatToShow = NodeFilter::SHOW_ALL, callable $aFilter = null) {
        $this->mCollection = new TreeWalker($aRoot, $aWhatToShow);
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
                        $node = $this->mCollection->nextNode();

                        if (!$node) {
                            return null;
                        }
                    } else {
                        $beforeNode = false;
                    }

                    break;

                case 'previous':
                    if ($beforeNode) {
                        $node = $this->mCollection->previousNode();

                        if (!$node) {
                            return null;
                        }
                    } else {
                        $beforeNode = true;
                    }

                    break;
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
