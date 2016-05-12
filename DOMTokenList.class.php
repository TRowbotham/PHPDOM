<?php
namespace phpjs;

use phpjs\elements\Element;
use phpjs\exceptions\InvalidCharacterError;
use phpjs\exceptions\SyntaxError;

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
    \Iterator
{
    protected $mAttrLocalName;
    protected $mElement;
    protected $mIndex;
    protected $mLength;
    protected $mPosition;
    protected $mTokens;

    public function __construct(Element $aElement, $aAttrLocalName)
    {
        $this->mAttrLocalName = $aAttrLocalName;
        $this->mElement = $aElement;
        $this->mIndex = [];
        $this->mLength = 0;
        $this->mPosition = 0;
        $this->mTokens = [];
        $attrList = $this->mElement->getAttributeList();
        $attrList->observe($this);
        $attr = $attrList->getAttrByNamespaceAndLocalName(
            null,
            $this->mAttrLocalName,
            $this->mElement
        );
        $value = $attr ? $attr->value : null;

        $this->onAttributeChanged(
            $this->mElement,
            $this->mAttrLocalName,
            $value,
            $value,
            null
        );
    }

    public function __destruct()
    {
        $this->mElement = null;
        $this->mIndex = null;
        $this->mTokens = null;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'length':
                return $this->mLength;

            case 'value':
                return $this->mElement->getAttributeList()->getAttrValue(
                    $this->mElement,
                    $this->mAttrLocalName
                );
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'value':
                $this->mElement->getAttributeList()->setAttrValue(
                    $this->mElement,
                    $this->mAttrLocalName,
                    $aValue
                );
        }
    }

    /**
     * Gets the value of the attribute of the associated element's associated
     * attribute's local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-dtl-serialize
     *
     * @return string
     */
    public function __toString()
    {
        return $this->mElement->getAttributeList()->getAttrValue(
            $this->mElement,
            $this->mAttrLocalName
        );
    }

    /**
     * Adds all the arguments to the token list except for those that
     * already exist in the list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-add
     *
     * @param string ...$aTokens One or more tokens to be added to the token
     *     list.
     *
     * @throws SyntaxError If the token is an empty string.
     *
     * @throws InvalidCharacterError If the token contains ASCII whitespace.
     */
    public function add(...$aTokens)
    {
        foreach ($aTokens as $token) {
            if ($token === '') {
                throw new SyntaxError();
            }

            if (preg_match('/\s/', $token)) {
                throw new InvalidCharacterError();
            }
        }

        foreach ($aTokens as $token) {
            if (!isset($this->mTokens[$token])) {
                $this->mIndex[] = $token;
                $this->mTokens[$token] = $this->mLength++;
            }
        }

        $this->mElement->getAttributeList()->setAttrValue(
            $this->mElement,
            $this->mAttrLocalName,
            Utils::serializeOrderedSet($this->mIndex)
        );
    }

    /**
     * Checks if the given token is contained in the list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-contains
     *
     * @param string $aToken A token to check against the token list.
     *
     * @return bool Returns true if the token is present, and false otherwise.
     *
     * @throws SyntaxError If the token is an empty string.
     *
     * @throws InvalidCharacterError If the token contains ASCII whitespace.
     */
    public function contains($aToken)
    {
        return isset($this->mTokens[$aToken]);
    }

    /**
     * Gets the number of tokens in the list.
     *
     * @return int
     */
    public function count()
    {
        return $this->mLength;
    }

    /**
     * Gets the current token that the iterator is pointing to.
     *
     * @return string
     */
    public function current()
    {
        return $this->mIndex[$this->mPosition];
    }

    /**
     * @see AttributeChangeObserver
     */
    public function onAttributeChanged(
        Element $aElement,
        $aLocalName,
        $aOldValue,
        $aValue,
        $aNamespace
    ) {
        if ($aLocalName === $this->mAttrLocalName && $aNamespace === null) {
            $this->mIndex = [];
            $this->mLength = 0;
            $this->mTokens = [];

            if ($aValue !== null) {
                foreach (Utils::parseOrderedSet($aValue) as $token) {
                    $this->mIndex[] = $token;
                    $this->mTokens[$token] = $this->mLength++;
                }
            }
        }
    }

    /**
     * Gets the token at the given index.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-item
     *
     * @param int $aIndex An integer index.
     *
     * @return string|null The token at the specified index or null if
     *     the index does not exist.
     */
    public function item($aIndex)
    {
        if ($aIndex >= $this->mLength) {
            return null;
        }

        return isset($this->mIndex[$aIndex]) ? $this->mIndex[$aIndex] : null;
    }

    /**
     * Gets the current position of the iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->mPosition;
    }

    /**
     * Advances the iterator to the next item.
     */
    public function next()
    {
        $this->mPosition++;
    }

    /**
     * Checks if the given index offset exists in the list of tokens.
     *
     * @param int $aIndex The integer index to check.
     *
     * @return bool
     */
    public function offsetExists($aIndex)
    {
        return $aIndex > $this->mLength;
    }

    /**
     * Gets the token at the given index.
     *
     * @param int $aIndex An integer index.
     *
     * @return string|null The token at the specified index or null if
     *     the index does not exist.
     */
    public function offsetGet($aIndex)
    {
        if ($aIndex >= $this->mLength) {
            return null;
        }

        return isset($this->mIndex[$aIndex]) ? $this->mIndex[$aIndex] : null;
    }

    /**
     * Setting a token using array notation is not permitted.  Use the add() or
     * toggle() methods instead.
     */
    public function offsetSet($aIndex, $aValue)
    {

    }

    /**
     * Unsetting a token using array notation is not permitted.  Use the
     * remove() or toggle() methods instead.
     */
    public function offsetUnset($aIndex)
    {

    }

    /**
     * Removes all the arguments from the token list.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-remove
     *
     * @param string ...$aTokens One or more tokens to be removed.
     *
     * @throws SyntaxError If the token is an empty string.
     *
     * @throws InvalidCharacterError If the token contains ASCII whitespace.
     */
    public function remove(...$aTokens)
    {
        foreach ($aTokens as $token) {
            if ($token === '') {
                throw new SyntaxError();
            }

            if (preg_match('/\s/', $token)) {
                throw new InvalidCharacterError();
            }
        }

        foreach ($aTokens as $token) {
            if (isset($this->mTokens[$token])) {
                array_splice($this->mIndex, $this->mTokens[$token], 1);
                unset($this->mTokens[$token]);
                $this->mLength--;
            }
        }

        $this->mElement->getAttributeList()->setAttrValue(
            $this->mElement,
            $this->mAttrLocalName,
            Utils::serializeOrderedSet($this->mIndex)
        );
    }

    /**
     * Replaces a token in the token list with another token.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-replace
     *
     * @param string $aToken The token to be replaced.
     *
     * @param string $aNewToken The token to be inserted.
     *
     * @throws SyntaxError If either token is an empty string.
     *
     * @throws InvalidCharacterError If either token contains ASCII whitespace.
     */
    public function replace($aToken, $aNewToken)
    {
        if ($aToken === '' || $aNewToken === '') {
            throw new SyntaxError();
        }

        if (preg_match('/\s/', $aToken) || preg_match('/\s/', $aNewToken)) {
            throw new InvalidCharacterError();
        }

        if (!isset($this->mTokens[$aToken])) {
            return;
        }

        $index = $this->mTokens[$aToken];
        unset($this->mTokens[$aToken]);
        array_splice($this->mIndex, $index, 1, [$aNewToken]);
        $this->mTokens[$aNewToken] = $index;

        $this->mElement->getAttributeList()->setAttrValue(
            $this->mElement,
            $this->mAttrLocalName,
            Utils::serializeOrderedSet($this->mIndex)
        );
    }

    /**
     * Rewinds the iterator to the beginning.
     */
    public function rewind()
    {
        $this->mPosition = 0;
    }

    /**
     * Checks if the token is a valid token for the associated attribute name,
     * if the associated attribute local name has a list of supported tokens.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-supports
     *
     * @param string $aToken The token to check.
     *
     * @return bool
     *
     * @throws TypeError If the associated attribute's local name does not
     *     define a list of supported tokens.
     */
    public function supports($aToken)
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
     * Toggles the presence of the given token in the token list.  If force is
     * true, the token is added to the list and it is removed from the list if
     * force is false.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-toggle
     *
     * @param string $aToken The token to be toggled.
     *
     * @param bool $aForce Optional. Whether or not the token should be
     *     forcefully added or removed.
     *
     * @return bool Returns true if the token is present, and false otherwise.
     *
     * @throws SyntaxError If either token is an empty string.
     *
     * @throws InvalidCharacterError If either token contains ASCII whitespace.
     */
    public function toggle($aToken, $aForce = null)
    {
        if ($aToken === '') {
            throw new SyntaxError();
        }

        if (preg_match('/\s/', $aToken)) {
            throw new InvalidCharacterError();
        }

        if (isset($this->mTokens[$aToken])) {
            if (!$aForce) {
                array_splice($this->mIndex, $this->mTokens[$aToken], 1);
                unset($this->mTokens[$aToken]);
                $this->mLength--;
                $this->mElement->getAttributeList()->setAttrValue(
                    $this->mElement,
                    $this->mAttrLocalName,
                    Utils::serializeOrderedSet($this->mIndex)
                );

                return false;
            } else {
                return true;
            }
        } else {
            if ($aForce === false) {
                return false;
            } else {
                $this->mIndex[] = $aToken;
                $this->mTokens[$aToken] = $this->mLength++;
                $this->mElement->getAttributeList()->setAttrValue(
                    $this->mElement,
                    $this->mAttrLocalName,
                    Utils::serializeOrderedSet($this->mIndex)
                );

                return true;
            }
        }
    }

    /**
     * Checks if the iterator's position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->mIndex[$this->mPosition]);
    }
}
