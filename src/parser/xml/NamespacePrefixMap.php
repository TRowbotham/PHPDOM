<?php
namespace Rowbot\DOM\Parser\XML;

/**
 * @see https://w3c.github.io/DOM-Parsing/#the-namespace-prefix-map
 */
class NamespacePrefixMap
{
    private $map;

    public function __construct()
    {
        $this->map = [];
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-retrieve-a-preferred-prefix-string
     * @param  [type] $namespace       [description]
     * @param  [type] $preferredPrefix [description]
     * @return [type]                  [description]
     */
    public function preferredPrefix($namespace, $preferredPrefix)
    {
        if (!isset($this->map[$namespace])) {
            return null;
        }

        foreach ($this->map[$namespace] as $prefix => $value) {
            if ($prefix === $preferredPrefix) {
                return $prefix;
            }
        }

        return $this->map[$namespace][count($this->map) - 1];
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-found
     * @param  [type]  $namespace [description]
     * @param  [type]  $prefix    [description]
     * @return boolean            [description]
     */
    public function hasPrefix($namespace, $prefix)
    {
        return isset($this->map[$namespace][$prefix]);
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-add
     * @param [type] $namespace [description]
     * @param [type] $prefix    [description]
     */
    public function add($namespace, $prefix)
    {
        if (!isset($this->map[$namespace])) {
            $this->map[$namespace] = [];
        }

        $this->map[$namespace][$prefix] = 0;
    }
}
