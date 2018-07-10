<?php
namespace Rowbot\DOM;

trait GetElementsBy
{
    /**
     * Returns a list of all the Element's that have all the given class names.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getelementsbyclassname
     *
     * @param string $className A space delimited string containing the classNames to search for.
     *
     * @return \Rowbot\DOM\Element\Element[]
     */
    public function getElementsByClassName($className): array
    {
        $classes = Utils::parseOrderedSet(
            Utils::DOMString($className)
        );
        $collection = [];

        if ($classes === '') {
            return $collection;
        }

        $nodeFilter = function ($node) use ($classes) {
            $hasClasses = false;

            foreach ($classes as $className) {
                if (!($hasClasses = $node->classList->contains($className))) {
                    break;
                }
            }

            if ($hasClasses) {
                return NodeFilter::FILTER_ACCEPT;
            }

            return NodeFilter::FILTER_SKIP;
        };
        $tw = new TreeWalker($this, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }

    /**
     * Returns an array of Elements with the specified local name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-getelementsbytagname
     *
     * @param string $localName The element's local name to search for. If given '*', all element decendants will be
     *                          returned.
     *
     * @return \Rowbot\DOM\Element\Element[] A list of Elements with the specified local name.
     */
    public function getElementsByTagName($localName): array
    {
        $collection = [];
        $localName = Utils::DOMString($localName);

        if ($localName === '*') {
            $nodeFilter = null;
        } elseif ($this instanceof Document) {
            $nodeFilter = function ($node) use ($localName) {
                $shouldAccept = ($node->namespaceURI === Namespaces::HTML
                    && $node->localName === Utils::toASCIILowercase($localName))
                    || ($node->namespaceURI === Namespaces::HTML
                    && $node->localName === $localName);

                if ($shouldAccept) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            };
        } else {
            $nodeFilter = function ($node) use ($localName) {
                if ($node->localName === $localName) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            };
        }

        $tw = new TreeWalker($this, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }

    /**
     * Returns a collection of Elements that match the given namespace and local
     * name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-getelementsbytagnamens
     *
     * @param string $namespace The namespaceURI to search for. If both namespace and local name are given '*', all
     *                          element decendants will be returned. If only namespace is given '*' all element
     *                          decendants matching only local name will be returned.
     * @param string $localName The Element's local name to search for. If both namespace and local name are given '*',
     *                          all element decendants will be returned.  If only local name is given '*' all element
     *                          decendants matching only namespace will be returned.
     *
     * @return \Rowbot\DOM\Element\Element[]
     */
    public function getElementsByTagNameNS(
        ?string $namespace,
        $localName
    ): array {
        if ($namespace === '') {
            $namespace = null;
        }

        $localName = Utils::DOMString($localName);
        $collection = [];

        if ($namespace === '*' && $localName === '*') {
            $nodeFilter = null;
        } elseif ($namespace === '*') {
            $nodeFilter = function ($node) use ($localName) {
                if ($node->localName === $localName) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            };
        } elseif ($localName === '*') {
            $nodeFilter =  function ($node) use ($namespace) {
                if ($node->namespaceURI === $namespace) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            };
        }

        $tw = new TreeWalker($this, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }
}
