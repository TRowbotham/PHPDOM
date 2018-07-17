<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\XML;

use function count;

/**
 * @see https://w3c.github.io/DOM-Parsing/#the-namespace-prefix-map
 */
class NamespacePrefixMap
{
    /**
     * @var array<string, array<string, int>>
     */
    private $map;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->map = [];
    }

    /**
     * Retrieves the preferred prefix string.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dfn-retrieving-a-preferred-prefix-string
     *
     * @param ?string $namespace
     * @param string  $preferredPrefix
     *
     * @return ?string
     */
    public function preferredPrefix($namespace, $preferredPrefix): ?string
    {
        if (!isset($this->map[$namespace])) {
            return null;
        }

        foreach ($this->map[$namespace] as $prefix => $value) {
            if ($prefix === $preferredPrefix) {
                return $prefix;
            }
        }

        return $prefix;
    }

    /**
     * Checks if the given prefix exists in the given namespace.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dfn-found
     *
     * @param ?string $namespace
     * @param string  $prefix
     *
     * @return bool
     */
    public function hasPrefix($namespace, $prefix)
    {
        return isset($this->map[$namespace][$prefix]);
    }

    /**
     * Associates the given prefix with the given namespace.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dfn-add
     *
     * @param ?string $namespace
     * @param string  $prefix
     *
     * @return void
     */
    public function add($namespace, $prefix)
    {
        if (!isset($this->map[$namespace])) {
            $this->map[$namespace] = [];
        }

        $this->map[$namespace][$prefix] = 0;
    }
}
