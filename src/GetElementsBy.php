<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Generator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Support\Collection\StringSet;

use function array_map;
use function in_array;

trait GetElementsBy
{
    /**
     * Returns a list of all the Element's that have all the given class names.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getelementsbyclassname
     *
     * @param string $classNames A space delimited string containing the classNames to search for.
     *
     * @return \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element>
     */
    public function getElementsByClassName(string $classNames): HTMLCollection
    {
        // 1. Let classes be the result of running the ordered set parser on classNames.
        $classes = StringSet::createFromString($classNames);

        // NOTE: When invoked with the same argument, the same HTMLCollection object may be returned
        // as returned by an earlier call.

        // 3. Return a HTMLCollection rooted at root, whose filter matches descendant elements
        // that have all their classes in classes.
        return new HTMLCollection($this, static function (self $root) use ($classes): Generator {
            // 2. If classes is the empty set, return an empty HTMLCollection.
            if ($classes->isEmpty()) {
                return;
            }

            $isQuirksMode = $root->nodeDocument->getMode() === DocumentMode::QUIRKS;

            if ($isQuirksMode) {
                $classes = array_map([Utils::class, 'toASCIILowercase'], $classes->all());
            }

            $node = $root;

            while (($node = $node->nextNode($root)) !== null) {
                if (!$node instanceof Element) {
                    continue;
                }

                // NOTE: The comparisons for the classes must be done in an ASCII
                // case-insensitive manner if root’s node document’s mode is "quirks", and in an
                // identical to manner otherwise.
                if ($isQuirksMode) {
                    $nodeClasses = array_map(
                        [Utils::class, 'toASCIILowercase'],
                        StringSet::createFromString($node->className)->all()
                    );

                    foreach ($classes as $className) {
                        if (!in_array($className, $nodeClasses, true)) {
                            continue 2;
                        }
                    }
                } else {
                    $classList = $node->classList;

                    foreach ($classes as $className) {
                        if (!$classList->contains($className)) {
                            continue 2;
                        }
                    }
                }

                yield $node;
            }
        });
    }

    /**
     * Returns an array of Elements with the specified local name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-getelementsbytagname
     *
     * @param string $qualifiedName The element's local name to search for. If given '*', all element decendants will be
     *                              returned.
     *
     * @return \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element> A list of Elements with the specified local name.
     */
    public function getElementsByTagName(string $qualifiedName): HTMLCollection
    {
        // NOTE: When invoked with the same argument, and as long as root’s node document’s type has
        // not changed, the same HTMLCollection object may be returned as returned by an earlier
        // call.

        // 1. If qualifiedName is "*" (U+002A), return a HTMLCollection rooted at root, whose filter
        // matches only descendant elements.
        if ($qualifiedName === '*') {
            return new HTMLCollection($this, static function (self $root): Generator {
                $node = $root;

                while (($node = $node->nextNode($root)) !== null) {
                    if ($node instanceof Element) {
                        yield $node;
                    }
                }
            });
        }

        // 2. Otherwise, if root’s node document is an HTML document, return a HTMLCollection rooted
        // at root, whose filter matches the following descendant elements:
        if ($this->nodeDocument->isHTMLDocument()) {
            return new HTMLCollection(
                $this,
                static function (self $root) use ($qualifiedName): Generator {
                    $node = $root;

                    while (($node = $node->nextNode($root)) !== null) {
                        if (!$node instanceof Element) {
                            continue;
                        }

                        $isHTMLNamespace = $node->namespaceURI === Namespaces::HTML;
                        $qName = $node->getQualifiedName();

                        // - Whose namespace is the HTML namespace and whose qualified name is
                        //   qualifiedName, in ASCII lowercase.
                        // - Whose namespace is not the HTML namespace and whose qualified name is
                        //   qualifiedName.
                        if (
                            ($isHTMLNamespace && $qName === Utils::toASCIILowercase($qualifiedName))
                            || (!$isHTMLNamespace && $qName === $qualifiedName)
                        ) {
                            yield $node;
                        }
                    }
                }
            );
        }

        // 3. Otherwise, return a HTMLCollection rooted at root, whose filter matches descendant
        // elements whose qualified name is qualifiedName.
        return new HTMLCollection(
            $this,
            static function (self $root) use ($qualifiedName): Generator {
                $node = $root;

                while (($node = $node->nextNode($root)) !== null) {
                    if ($node instanceof Element && $node->getQualifiedName() === $qualifiedName) {
                        yield $node;
                    }
                }
            }
        );
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
     * @return \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element>
     */
    public function getElementsByTagNameNS(?string $namespace, string $localName): HTMLCollection
    {
        // NOTE: When invoked with the same arguments, the same HTMLCollection object may be
        // returned as returned by an earlier call.

        // 1. If namespace is the empty string, set it to null.
        if ($namespace === '') {
            $namespace = null;
        }

        // 2. If both namespace and localName are "*" (U+002A), return a HTMLCollection rooted at
        // root, whose filter matches descendant elements.
        if ($namespace === '*' && $localName === '*') {
            return new HTMLCollection($this, static function (self $root): Generator {
                $node = $root;

                while (($node = $node->nextNode($root)) !== null) {
                    if ($node instanceof Element) {
                        yield $node;
                    }
                }
            });
        }

        // 3. Otherwise, if namespace is "*" (U+002A), return a HTMLCollection rooted at root, whose
        // filter matches descendant elements whose local name is localName.
        if ($namespace === '*') {
            return new HTMLCollection(
                $this,
                static function (self $root) use ($localName): Generator {
                    $node = $root;

                    while (($node = $node->nextNode($root)) !== null) {
                        if ($node instanceof Element && $node->localName === $localName) {
                            yield $node;
                        }
                    }
                }
            );
        }

        // 4. Otherwise, if localName is "*" (U+002A), return a HTMLCollection rooted at root, whose
        // filter matches descendant elements whose namespace is namespace.
        if ($localName === '*') {
            return new HTMLCollection(
                $this,
                static function (self $root) use ($namespace): Generator {
                    $node = $root;

                    while (($node = $node->nextNode($root)) !== null) {
                        if ($node instanceof Element && $node->namespaceURI === $namespace) {
                            yield $node;
                        }
                    }
                }
            );
        }

        // 5. Otherwise, return a HTMLCollection rooted at root, whose filter matches descendant
        // elements whose namespace is namespace and local name is localName.
        return new HTMLCollection(
            $this,
            static function (self $root) use ($namespace, $localName): Generator {
                $node = $root;

                while (($node = $node->nextNode($root)) !== null) {
                    if (
                        $node instanceof Element
                        && $node->namespaceURI === $namespace
                        && $node->localName === $localName
                    ) {
                        yield $node;
                    }
                }
            }
        );
    }
}
