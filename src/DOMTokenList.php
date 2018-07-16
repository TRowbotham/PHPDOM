<?php
namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Iterator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Support\Collection\StringSet;
use Rowbot\DOM\Support\Stringable;

use function preg_match;

/**
 * @see https://dom.spec.whatwg.org/#interface-domtokenlist
 *
 * @property-read int    $length Returns the number of tokens in the list.
 * @property      string $value
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

    /**
     * Constructor.
     *
     * @param \Rowbot\DOM\Element\Element $element
     * @param string                      $attrLocalName
     *
     * @return void
     */
    public function __construct(Element $element, string $attrLocalName)
    {
        $this->attrLocalName = $attrLocalName;
        $this->element = $element;
        $attrList = $this->element->getAttributeList();
        $attrList->observe($this);
        $attr = $attrList->getAttrByNamespaceAndLocalName(
            null,
            $this->attrLocalName
        );
        $value = $attr ? $attr->getValue() : '';

        $this->onAttributeChanged(
            $this->element,
            $this->attrLocalName,
            $value,
            $value,
            null
        );
    }

    /**
     * @param string $name
     *
     * @return string|int
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

    /**
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    public function __set(string $name, $value): string
    {
        switch ($name) {
            case 'value':
                $this->element->getAttributeList()->setAttrValue(
                    $this->attrLocalName,
                    Utils::DOMString($value)
                );
        }
    }

    /**
     * Gets the token at the given index.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-item
     *
     * @param int $index An integer index.
     *
     * @return string|null The token at the specified index or null if the index does not exist.
     */
    public function item($index): ?string
    {
        return $this->tokens->offsetGet($index);
    }

    /**
     * Adds all the arguments to the token list except for those that
     * already exist in the list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-add
     *
     * @param string ...$tokens One or more tokens to be added to the token list.
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError           If the token is an empty string.
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If the token contains ASCII whitespace.
     *
     * @return void
     */
    public function add(...$tokens): void
    {
        foreach ($tokens as &$token) {
            $token = Utils::DOMString($token);

            if ($token === '') {
                throw new SyntaxError();
            }

            if (preg_match(Utils::ASCII_WHITESPACE, $token)) {
                throw new InvalidCharacterError();
            }
        }

        unset($token);

        foreach ($tokens as $token) {
            $this->tokens->append($token);
        }

        $this->update();
    }

    /**
     * Checks if the given token is contained in the list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-contains
     *
     * @param string $token A token to check against the token list.
     *
     * @return bool Returns true if the token is present, and false otherwise.
     */
    public function contains($token): bool
    {
        return $this->tokens->contains($token);
    }

    /**
     * Removes all the arguments from the token list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-remove
     *
     * @param string ...$tokens One or more tokens to be removed.
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError           If the token is an empty string.
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If the token contains ASCII whitespace.
     *
     * @return void
     */
    public function remove(...$tokens): void
    {
        foreach ($tokens as &$token) {
            $token = Utils::DOMString($token);

            if ($token === '') {
                throw new SyntaxError();
            }

            if (preg_match(Utils::ASCII_WHITESPACE, $token)) {
                throw new InvalidCharacterError();
            }
        }

        unset($token);

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
     * @param string $token The token to be toggled.
     * @param bool   $force (optional) Whether or not the token should be
     *                                 forcefully added or removed.
     *
     * @return bool Returns true if the token is present, and false otherwise.
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError           If either token is an empty string.
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If either token contains ASCII whitespace.
     */
    public function toggle($token, bool $force = false): bool
    {
        $token = Utils::DOMString($token);

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
     * @param string $token    The token to be replaced.
     * @param string $newToken The token to be inserted.
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError           If either token is an empty string.
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If either token contains ASCII whitespace.
     *
     * @return bool
     */
    public function replace($token, $newToken): bool
    {
        $token = Utils::DOMString($token);
        $newToken = Utils::DOMString($newToken);

        if ($token === '' || $newToken === '') {
            throw new SyntaxError();
        }

        if (preg_match(Utils::ASCII_WHITESPACE, $token)
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
     * @param string $token The token to check.
     *
     * @return bool
     *
     * @throws \Rowbot\DOM\Exception\TypeError If the associated attribute's local name does not define a list of
     *                                         supported tokens.
     */
    public function supports($token): bool
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
     *
     * @return void
     */
    private function update()
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

        $attrList->setAttrValue(
            $this->attrLocalName,
            $this->tokens->toString()
        );
    }

    /**
     * Gets the value of the attribute of the associated element's associated
     * attribute's local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-dtl-serialize
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->element->getAttributeList()->getAttrValue(
            $this->attrLocalName
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Checks if the given index offset exists in the list of tokens.
     *
     * @param int $index The integer index to check.
     *
     * @return bool
     */
    public function offsetExists($index): bool
    {
        return $this->tokens->offsetExists($index);
    }

    /**
     * Gets the token at the given index.
     *
     * @param int $index An integer index.
     *
     * @return string|null The token at the specified index or null if the index does not exist.
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
     *
     * @return void
     */
    public function offsetSet($index, $token): void
    {
    }

    /**
     * Unsetting a token using array notation is not permitted.  Use the remove() or toggle() methods instead.
     *
     * @param int $index
     *
     * @return void
     */
    public function offsetUnset($index): void
    {
    }

    /**
     * Gets the number of tokens in the list.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->tokens->count();
    }

    /**
     * Gets the current token that the iterator is pointing to.
     *
     * @return string
     */
    public function current(): string
    {
        return $this->tokens->current();
    }

    /**
     * Gets the current position of the iterator.
     *
     * @return int
     */
    public function key(): int
    {
        return $this->tokens->key();
    }

    /**
     * Advances the iterator to the next item.
     *
     * @return void
     */
    public function next(): void
    {
        $this->tokens->next();
    }

    /**
     * Rewinds the iterator to the beginning.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->tokens->rewind();
    }

    /**
     * Checks if the iterator's position is valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->tokens->valid();
    }

    /**
     * {@inheritDoc}
     */
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
