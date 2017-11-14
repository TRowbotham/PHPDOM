<?php
namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Iterator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Support\OrderedSet;
use Rowbot\DOM\Support\Stringable;

/**
 * @see https://dom.spec.whatwg.org/#interface-domtokenlist
 *
 * @property-read int $length Returns the number of tokens in the list.
 *
 * @property string $value
 */
class DOMTokenList implements
    ArrayAccess,
    AttributeChangeObserver,
    Countable,
    Iterator,
    Stringable
{
    protected $attrLocalName;
    protected $element;
    protected $tokens;

    public function __construct(Element $element, $attrLocalName)
    {
        $this->attrLocalName = $attrLocalName;
        $this->element = $element;
        $this->tokens = new OrderedSet();
        $attrList = $this->element->getAttributeList();
        $attrList->observe($this);
        $attr = $attrList->getAttrByNamespaceAndLocalName(
            null,
            $this->attrLocalName
        );
        $value = $attr ? $attr->value : null;

        $this->onAttributeChanged(
            $this->element,
            $this->attrLocalName,
            $value,
            $value,
            null
        );
    }

    public function __get($name)
    {
        switch ($name) {
            case 'length':
                return $this->tokens->count();

            case 'value':
                return $this->toString();
        }
    }

    public function __set($name, $value)
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
     * @return string|null The token at the specified index or null if
     *     the index does not exist.
     */
    public function item($index)
    {
        return $this->tokens->offsetGet($index);
    }

    /**
     * Adds all the arguments to the token list except for those that
     * already exist in the list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-add
     *
     * @param string ...$tokens One or more tokens to be added to the token
     *     list.
     *
     * @throws SyntaxError If the token is an empty string.
     *
     * @throws InvalidCharacterError If the token contains ASCII whitespace.
     */
    public function add(...$tokens)
    {
        foreach ($tokens as &$token) {
            $token = Utils::DOMString($token);

            if ($token === '') {
                throw new SyntaxError();
                return;
            }

            if (preg_match('/\s/', $token)) {
                throw new InvalidCharacterError();
                return;
            }
        }

        foreach ($tokens as $token) {
            $this->tokens->append($token);
        }

        $this->element->getAttributeList()->setAttrValue(
            $this->attrLocalName,
            Utils::serializeOrderedSet($this->tokens->values())
        );
    }

    /**
     * Checks if the given token is contained in the list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-contains
     *
     * @param string $token A token to check against the token list.
     *
     * @return bool Returns true if the token is present, and false otherwise.
     *
     * @throws SyntaxError If the token is an empty string.
     *
     * @throws InvalidCharacterError If the token contains ASCII whitespace.
     */
    public function contains($token)
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
     * @throws SyntaxError If the token is an empty string.
     *
     * @throws InvalidCharacterError If the token contains ASCII whitespace.
     */
    public function remove(...$tokens)
    {
        foreach ($tokens as &$token) {
            $token = Utils::DOMString($token);

            if ($token === '') {
                throw new SyntaxError();
                return;
            }

            if (preg_match('/\s/', $token)) {
                throw new InvalidCharacterError();
                return;
            }
        }

        foreach ($tokens as $token) {
            $this->tokens->remove($token);
        }

        $this->element->getAttributeList()->setAttrValue(
            $this->attrLocalName,
            Utils::serializeOrderedSet($this->tokens->values())
        );
    }

    /**
     * Toggles the presence of the given token in the token list.  If force is
     * true, the token is added to the list and it is removed from the list if
     * force is false.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-toggle
     *
     * @param string $token The token to be toggled.
     *
     * @param bool $force Optional. Whether or not the token should be
     *     forcefully added or removed.
     *
     * @return bool Returns true if the token is present, and false otherwise.
     *
     * @throws SyntaxError If either token is an empty string.
     *
     * @throws InvalidCharacterError If either token contains ASCII whitespace.
     */
    public function toggle($token, $force = null)
    {
        $token = Utils::DOMString($token);

        if ($token === '') {
            throw new SyntaxError();
            return;
        }

        if (preg_match('/\s/', $token)) {
            throw new InvalidCharacterError();
            return;
        }

        if ($this->tokens->contains($token)) {
            if (!$force) {
                $this->tokens->remove($token);
                $this->element->getAttributeList()->setAttrValue(
                    $this->attrLocalName,
                    Utils::serializeOrderedSet($this->tokens->values())
                );

                return false;
            }

            return true;
        }

        if ($force === false) {
            return false;
        }

        $this->tokens->append($token);
        $this->element->getAttributeList()->setAttrValue(
            $this->attrLocalName,
            Utils::serializeOrderedSet($this->tokens->values())
        );

        return true;
    }

    /**
     * Replaces a token in the token list with another token.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-replace
     *
     * @param string $token    The token to be replaced.
     *
     * @param string $newToken The token to be inserted.
     *
     * @throws SyntaxError           If either token is an empty string.
     *
     * @throws InvalidCharacterError If either token contains ASCII whitespace.
     */
    public function replace($token, $newToken)
    {
        $token = Utils::DOMString($token);
        $newToken = Utils::DOMString($newToken);

        if ($token === '' || $newToken === '') {
            throw new SyntaxError();
            return;
        }

        if (preg_match('/\s/', $token) || preg_match('/\s/', $newToken)) {
            throw new InvalidCharacterError();
            return;
        }

        if (!$this->tokens->contains($token)) {
            return;
        }

        $this->tokens->replace($token, $newToken);
        $this->element->getAttributeList()->setAttrValue(
            $this->attrLocalName,
            Utils::serializeOrderedSet($this->tokens->values())
        );
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
     * @throws TypeError If the associated attribute's local name does not
     *     define a list of supported tokens.
     */
    public function supports($token)
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
     * Gets the value of the attribute of the associated element's associated
     * attribute's local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-dtl-serialize
     *
     * @return string
     */
    public function toString()
    {
        return $this->element->getAttributeList()->getAttrValue(
            $this->attrLocalName
        );
    }

    public function __toString()
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
    public function offsetExists($index)
    {
        return $this->tokens->offsetExists($index);
    }

    /**
     * Gets the token at the given index.
     *
     * @param int $index An integer index.
     *
     * @return string|null The token at the specified index or null if
     *     the index does not exist.
     */
    public function offsetGet($index)
    {
        return $this->tokens->offsetGet($index);
    }

    /**
     * Setting a token using array notation is not permitted.  Use the add() or
     * toggle() methods instead.
     */
    public function offsetSet($index, $token)
    {
    }

    /**
     * Unsetting a token using array notation is not permitted.  Use the
     * remove() or toggle() methods instead.
     */
    public function offsetUnset($index)
    {
    }

    /**
     * Gets the number of tokens in the list.
     *
     * @return int
     */
    public function count()
    {
        return $this->tokens->count();
    }

    /**
     * Gets the current token that the iterator is pointing to.
     *
     * @return string
     */
    public function current()
    {
        return $this->tokens->current();
    }

    /**
     * Gets the current position of the iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->tokens->key();
    }

    /**
     * Advances the iterator to the next item.
     */
    public function next()
    {
        $this->tokens->next();
    }

    /**
     * Rewinds the iterator to the beginning.
     */
    public function rewind()
    {
        $this->tokens->rewind();
    }

    /**
     * Checks if the iterator's position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->tokens->valid();
    }

    /**
     * @see AttributeChangeObserver
     */
    public function onAttributeChanged(
        Element $element,
        $localName,
        $oldValue,
        $value,
        $namespace
    ) {
        if ($localName === $this->attrLocalName && $namespace === null) {
            $this->tokens->clear();

            if ($value !== null) {
                foreach (Utils::parseOrderedSet($value) as $token) {
                    $this->tokens->append($token);
                }
            }
        }
    }
}
