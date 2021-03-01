<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\XML;

/**
 * @see https://w3c.github.io/DOM-Parsing/#the-namespace-prefix-map
 */
class NamespacePrefixMap
{
    /**
     * @var array<string, array<string, int>>
     */
    private $map;

    public function __construct()
    {
        $this->map = [];
    }

    /**
     * Retrieves the preferred prefix string.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dfn-retrieving-a-preferred-prefix-string
     */
    public function preferredPrefix(?string $namespace, ?string $preferredPrefix): ?string
    {
        if (!isset($this->map[$namespace])) {
            return null;
        }

        $lastPrefix = null;

        foreach ($this->map[$namespace] as $prefix => $value) {
            if ($prefix === $preferredPrefix) {
                return $prefix;
            }

            $lastPrefix = $prefix;
        }

        return $lastPrefix;
    }

    /**
     * Checks if the given prefix exists in the given namespace.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dfn-found
     */
    public function hasPrefix(?string $namespace, string $prefix): bool
    {
        return isset($this->map[$namespace][$prefix]);
    }

    /**
     * Associates the given prefix with the given namespace.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dfn-add
     */
    public function add(?string $namespace, string $prefix): void
    {
        if (!isset($this->map[$namespace])) {
            $this->map[$namespace] = [];
        }

        $this->map[$namespace][$prefix] = 0;
    }
}
