<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Iterator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Support\Collection\StringSet;
use Rowbot\DOM\Support\Stringable;

use function func_num_args;
use function preg_match;

/**
 * @see https://dom.spec.whatwg.org/#interface-domtokenlist
 *
 * @property string $value
 *
 * @property-read int $length Returns the number of tokens in the list.
 *
 * @implements \ArrayAccess<int, string>
 * @implements \Iterator<int, string>
 */
final class DOMTokenList implements
    ArrayAccess,
    AttributeChangeObserver,
    Countable,
    Iterator,
    Stringable
{
    /**
     * @var string
     */
    private $attrLocalName;

    /**
     * @var \Rowbot\DOM\Element\Element
     */
    private $element;

    /**
     * @var \Rowbot\DOM\Support\Collection\StringSet
     */
    private $tokens;

    public function __construct(Element $element, string $attrLocalName)
    {
        $this->attrLocalName = $attrLocalName;
        $this->element = $element;
        $attrList = $this->element->getAttributeList();
        $attrList->observe($this);
        $value = $attrList->getAttrValue($attrLocalName);
        $this->onAttributeChanged($this->element, $this->attrLocalName, $value, $value, null);
    }

    /**
     * @return int|string
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'length':
                return $this->tokens->count();

            case 'value':
                return $this->toString();
        }
    }

    public function __set(string $name, string $value): void
    {
        switch ($name) {
            case 'value':
                $this->element->getAttributeList()->setAttrValue($this->attrLocalName, $value);
        }
    }

    /**
     * Gets the token at the given index.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-item
     */
    public function item(int $index): ?string
    {
        return $this->tokens->offsetGet($index);
    }

    /**
     * Adds all the arguments to the token list except for those that
     * already exist in the list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-add
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError           If the token is an empty string.
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If the token contains ASCII whitespace.
     */
    public function add(string ...$tokens): void
    {
        foreach ($tokens as $token) {
            if ($token === '') {
                throw new SyntaxError();
            }

            if (preg_match(Utils::ASCII_WHITESPACE, $token)) {
                throw new InvalidCharacterError();
            }
        }

        foreach ($tokens as $token) {
            $this->tokens->append($token);
        }

        $this->update();
    }

    /**
     * Checks if the given token is contained in the list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-contains
     */
    public function contains(string $token): bool
    {
        return $this->tokens->contains($token);
    }

    /**
     * Removes all the arguments from the token list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-remove
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError           If the token is an empty string.
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If the token contains ASCII whitespace.
     */
    public function remove(string ...$tokens): void
    {
        foreach ($tokens as $token) {
            if ($token === '') {
                throw new SyntaxError();
            }

            if (preg_match(Utils::ASCII_WHITESPACE, $token)) {
                throw new InvalidCharacterError();
            }
        }

        foreach ($tokens as $token) {
            $this->tokens->remove($token);
        }

        $this->update();
    }

    /**
     * Toggles the presence of the given token in the token list.  If force is
     * true, the token is added to the list and it is removed from the list if
     * force is false.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-toggle
     *
     * @return bool Returns true if the token is present, and false otherwise.
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError           If either token is an empty string.
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If either token contains ASCII whitespace.
     */
    public function toggle(string $token, bool $force = false): bool
    {
        if ($token === '') {
            throw new SyntaxError();
        }

        if (preg_match(Utils::ASCII_WHITESPACE, $token)) {
            throw new InvalidCharacterError();
        }

        $forceIsGiven = func_num_args() > 1;

        if ($this->tokens->contains($token)) {
            if ($forceIsGiven === false || $force === false) {
                $this->tokens->remove($token);
                $this->update();

                return false;
            }

            return true;
        }

        if ($forceIsGiven === false || $force === true) {
            $this->tokens->append($token);
            $this->update();

            return true;
        }

        return false;
    }

    /**
     * Replaces a token in the token list with another token.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-replace
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError           If either token is an empty string.
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If either token contains ASCII whitespace.
     */
    public function replace(string $token, string $newToken): bool
    {
        if ($token === '' || $newToken === '') {
            throw new SyntaxError();
        }

        if (
            preg_match(Utils::ASCII_WHITESPACE, $token)
            || preg_match(Utils::ASCII_WHITESPACE, $newToken)
        ) {
            throw new InvalidCharacterError();
        }

        if (!$this->tokens->contains($token)) {
            return false;
        }

        $this->tokens->replace($token, $newToken);
        $this->update();

        return true;
    }

    /**
     * Checks if the token is a valid token for the associated attribute name,
     * if the associated attribute local name has a list of supported tokens.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-supports
     *
     * @throws \Rowbot\DOM\Exception\TypeError If the associated attribute's local name does not define a list of
     *                                         supported tokens.
     */
    public function supports(string $token): bool
    {
        // TODO: This may not be worth implementing since we cannot accurately
        //     determine which values any particular browser actually supports.
        // If the associated attribute’s local name does not define supported
        //     tokens, throw a TypeError.
        // Let lowercase token be a copy of token, converted to ASCII lowercase.
        // If lowercase token is present in supported tokens, return true.
        // Return false.
        return true;
    }

    /**
     * Runs the token list's update steps.
     *
     * @see https://dom.spec.whatwg.org/#concept-dtl-update
     */
    private function update(): void
    {
        $attrList = $this->element->getAttributeList();
        $attr = $attrList->getAttrByNamespaceAndLocalName(
            null,
            $this->attrLocalName
        );

        // Don't create an empty attribute.
        if ($attr === null && $this->tokens->isEmpty()) {
            return;
        }

        $attrList->setAttrValue($this->attrLocalName, $this->tokens->toString());
    }

    /**
     * Gets the value of the attribute of the associated element's associated
     * attribute's local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-dtl-serialize
     */
    public function toString(): string
    {
        return $this->element->getAttributeList()->getAttrValue($this->attrLocalName);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Checks if the given index offset exists in the list of tokens.
     *
     * @param int $index The integer index to check.
     */
    public function offsetExists($index): bool
    {
        return $this->tokens->offsetExists($index);
    }

    /**
     * Gets the token at the given index.
     *
     * @param int $index
     */
    public function offsetGet($index): ?string
    {
        return $this->tokens->offsetGet($index);
    }

    /**
     * Setting a token using array notation is not permitted.  Use the add() or toggle() methods instead.
     *
     * @param int    $index
     * @param string $token
     */
    public function offsetSet($index, $token): void
    {
    }

    /**
     * Unsetting a token using array notation is not permitted.  Use the remove() or toggle() methods instead.
     *
     * @param int $index
     */
    public function offsetUnset($index): void
    {
    }

    /**
     * Gets the number of tokens in the list.
     */
    public function count(): int
    {
        return $this->tokens->count();
    }

    /**
     * Gets the current token that the iterator is pointing to.
     */
    public function current(): string
    {
        return $this->tokens->current();
    }

    /**
     * Gets the current position of the iterator.
     */
    public function key(): int
    {
        return $this->tokens->key();
    }

    /**
     * Advances the iterator to the next item.
     */
    public function next(): void
    {
        $this->tokens->next();
    }

    /**
     * Rewinds the iterator to the beginning.
     */
    public function rewind(): void
    {
        $this->tokens->rewind();
    }

    /**
     * Checks if the iterator's position is valid.
     */
    public function valid(): bool
    {
        return $this->tokens->valid();
    }

    public function onAttributeChanged(
        Element $element,
        string $localName,
        ?string $oldValue,
        ?string $value,
        ?string $namespace
    ): void {
        if ($localName === $this->attrLocalName && $namespace === null) {
            if ($value === null) {
                $this->tokens->clear();

                return;
            }

            $this->tokens = StringSet::createFromString($value);
        }
    }
}
