<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\SyntaxError;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#domstringmap
 */
final class DOMStringMap implements ArrayAccess, IteratorAggregate
{
    /**
     * @var \Rowbot\DOM\Element
     */
    private $element;

    public function __construct(Element $element)
    {
        $this->element = $element;
    }

    public function __get(string $name)
    {
        $pairs = $this->getPairs();

        return $pairs[$name] ?? null;
    }

    public function __set(string $name, $value)
    {
        $this->setAttribute($name, (string) $value);
    }

    public function __isset(string $name)
    {
        return isset($this->getPairs()[$name]);
    }

    public function __unset(string $name)
    {
        $this->removeAttribute($name);
    }

    public function offsetExists($name)
    {
        return isset($this->getPairs()[(string) $name]);
    }

    public function offsetGet($name)
    {
        return $this->getPairs()[(string) $name] ?? null;
    }

    public function offsetSet($name, $value)
    {
        $this->setAttribute((string) $name, (string) $value);
    }

    public function offsetUnset($name)
    {
        $this->removeAttribute((string) $name);
    }

    public function getIterator()
    {
        return new ArrayIterator(array_keys($this->getPairs()));
    }

    private function getPairs()
    {
        $list = [];

        foreach ($this->element->getAttributeList() as $attr) {
            $name = $attr->getLocalName();

            if (strncmp($name, 'data-', 5) !== 0 || $attr->getNamespace() !== null) {
                continue;
            }

            if (preg_match('/-[A-Z]/', $name, $maches, 0, 5) === 1) {
                continue;
            }

            $parts = explode('-', $name);
            $parts[0] = '';

            for ($i = 2, $length = count($parts); $i < $length; ++$i) {
                if ($parts[$i] === '' || !ctype_lower($parts[$i][0])) {
                    $parts[$i] = '-' . $parts[$i];

                    continue;
                }

                $parts[$i] = ucfirst($parts[$i]);
            }

            $list[implode('', $parts)] = $attr->getValue();
        }

        return $list;
    }

    private function nameToKebab(string $name): string
    {
        $result = preg_replace_callback('/([A-Z])([^A-Z]*)/', static function (array $matches): string {
            return '-' . strtolower($matches[1]) . $matches[2];
        }, $name);

        return "data-{$result}";
    }

    private function setAttribute(string $name, string $value): void
    {
        if (preg_match('/-[a-z]/', $name) === 1) {
            throw new SyntaxError();
        }

        $kebabName = $this->nameToKebab($name);

        if (preg_match(Namespaces::NAME_PRODUCTION, $kebabName) !== 1) {
            throw new InvalidCharacterError();
        }

        $this->element->getAttributeList()->setAttrValue($kebabName, (string) $value);
    }

    private function removeAttribute(string $name): void
    {
        if (!isset($this->getPairs()[$name])) {
            return;
        }

        $this->element->getAttributeList()->removeAttrByNamespaceAndLocalName(null, $this->nameToKebab($name));
    }
}
