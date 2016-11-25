<?php
namespace phpjs;

use phpjs\elements\Element;
use phpjs\exceptions\InvalidCharacterError;
use phpjs\exceptions\SyntaxError;
use phpjs\support\Stringifier;

/**
 * @see https://dom.spec.whatwg.org/#interface-domtokenlist
 *
 * @property-read int $length Returns the number of tokens in the list.
 *
 * @property string $value
 */
class DOMTokenList implements
    \ArrayAccess,
    AttributeChangeObserver,
    \Countable,
    \Iterator,
    Stringifier
{
    protected $attrLocalName;
    protected $element;
    protected $index;
    protected $length;
    protected $position;
    protected $tokens;

    public function __construct(Element $element, $attrLocalName)
    {
        $this->attrLocalName = $attrLocalName;
        $this->element = $element;
        $this->index = [];
        $this->length = 0;
        $this->position = 0;
        $this->tokens = [];
        $attrList = $this->element->getAttributeList();
        $attrList->observe($this);
        $attr = $attrList->getAttrByNamespaceAndLocalName(
            null,
            $this->attrLocalName,
            $this->element
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

    public function __destruct()
    {
        $this->element = null;
        $this->index = null;
        $this->tokens = null;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'length':
                return $this->length;

            case 'value':
                return $this->element->getAttributeList()->getAttrValue(
                    $this->element,
                    $this->attrLocalName
                );
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'value':
                $this->element->getAttributeList()->setAttrValue(
                    $this->element,
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
        if ($index >= $this->length) {
            return null;
        }

        return isset($this->index[$index]) ? $this->index[$index] : null;
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
            if (!isset($this->tokens[$token])) {
                $this->index[] = $token;
                $this->tokens[$token] = $this->length++;
            }
        }

        $this->element->getAttributeList()->setAttrValue(
            $this->element,
            $this->attrLocalName,
            Utils::serializeOrderedSet($this->index)
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
        return isset($this->tokens[Utils::DOMString($token)]);
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
            if (isset($this->tokens[$token])) {
                array_splice($this->index, $this->tokens[$token], 1);
                unset($this->tokens[$token]);
                $this->length--;
            }
        }

        $this->element->getAttributeList()->setAttrValue(
            $this->element,
            $this->attrLocalName,
            Utils::serializeOrderedSet($this->index)
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
        }

        if (preg_match('/\s/', $token)) {
            throw new InvalidCharacterError();
        }

        if (isset($this->tokens[$token])) {
            if (!$force) {
                array_splice($this->index, $this->tokens[$token], 1);
                unset($this->tokens[$token]);
                $this->length--;
                $this->element->getAttributeList()->setAttrValue(
                    $this->element,
                    $this->attrLocalName,
                    Utils::serializeOrderedSet($this->index)
                );

                return false;
            }

            return true;
        }

        if ($force === false) {
            return false;
        } else {
            $this->index[] = $token;
            $this->tokens[$token] = $this->length++;
            $this->element->getAttributeList()->setAttrValue(
                $this->element,
                $this->attrLocalName,
                Utils::serializeOrderedSet($this->index)
            );

            return true;
        }
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

        if (!isset($this->tokens[$token])) {
            return;
        }

        $index = $this->tokens[$token];
        unset($this->tokens[$token]);
        array_splice($this->index, $index, 1, [$newToken]);
        $this->tokens[$newToken] = $index;

        $this->element->getAttributeList()->setAttrValue(
            $this->element,
            $this->attrLocalName,
            Utils::serializeOrderedSet($this->index)
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
            $this->element,
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
        return $index > $this->length;
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
        if ($index >= $this->length) {
            return null;
        }

        return isset($this->index[$index]) ? $this->index[$index] : null;
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
        return $this->length;
    }

    /**
     * Gets the current token that the iterator is pointing to.
     *
     * @return string
     */
    public function current()
    {
        return $this->index[$this->position];
    }

    /**
     * Gets the current position of the iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Advances the iterator to the next item.
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Rewinds the iterator to the beginning.
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Checks if the iterator's position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->index[$this->position]);
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
            $this->index = [];
            $this->length = 0;
            $this->tokens = [];

            if ($value !== null) {
                foreach (Utils::parseOrderedSet($value) as $token) {
                    $this->index[] = $token;
                    $this->tokens[$token] = $this->length++;
                }
            }
        }
    }
}
