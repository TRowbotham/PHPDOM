<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DOMTokenList
// https://dom.spec.whatwg.org/#interface-domtokenlist

require_once 'Exceptions.class.php';

class DOMTokenList implements ArrayAccess, Iterator {
    protected $mAttrLocalName;
    protected $mTokens;

    private $mElement;
    private $mPosition;

    public function __construct(Element $aElement, $aAttrLocalName) {
        $this->mAttrLocalName = $aAttrLocalName;
        $this->mElement = $aElement;
        $this->mPosition = 0;
        $this->mTokens = [];
    }

    public function __get($aName) {
        switch ($aName) {
            case 'length':
                return count($this->mTokens);
        }
    }

    /**
     * Adds all arguments to the token list except for those that
     * already exist in the list.  A SyntaxError will be thrown if
     * one of the tokens is an empty string and a InvalidCharacterError
     * will be thrown if one of the tokens contains ASCII whitespace.
     * @param string $aTokens... One or more tokens to be added to the token
     *                           list.
     */
    public function add($aTokens) {
        if (!func_num_args()) {
            return;
        }

        $tokens = func_get_args();
        array_walk($tokens, array($this, 'checkToken'));

        $this->_add($tokens);
        $this->update();
    }

    /**
     * Returns true if the token is present, and false otherwise.
     * A SyntaxError will be thrown if one of the tokens is an empty string
     * and a InvalidCharacterError will be thrown if one of the tokens contains
     * ASCII whitespace.
     * @param  string $aToken A token to check against the token list
     * @return bool           Returns true if the token is present, and false
     *                        otherwise.
     */
    public function contains($aToken) {
        $this->checkToken($aToken);

        return $this->_contains($aToken);
    }

    /**
     * Returns the iterator's current element.
     * @return string|null
     */
    public function current() {
        return $this->item($this->mPosition);
    }

    /**
     * Returns the token at the specified index.
     * @param  int    $aIndex An integer index.
     * @return string         The token at the specified index or null if the
     *                        index does not exist.
     */
    public function item($aIndex) {
        return isset($this->mTokens[$aIndex]) ? $this->mTokens[$aIndex] : null;
    }

    /**
     * Returns the iterator's current pointer.
     * @return int
     */
    public function key() {
        return $this->mPosition;
    }

    /**
     * Advances the iterator's pointer by 1.
     */
    public function next() {
        $this->mPosition++;
    }

    /**
     * Used with isset() to check if there is a token at the specified index
     * location.
     * @param  int  $aOffset An integer index.
     * @return bool          Returns true if the index exists in the token list,
     *                       otherwise false.
     */
    public function offsetExists($aOffset) {
        return isset($this->mTokens[$aOffset]);
    }

    /**
     * Returns the token at the specified index.
     * @param  int    $aOffset An integer index.
     * @return string          The token at the specified index or null if the
     *                         index does not exist.
     */
    public function offsetGet($aOffset) {
        return $this->item($aOffset);
    }

    /**
     * Tokens cannont be created or their values modified using array notation.
     */
    public function offsetSet($aOffset, $aValue) {}
    public function offsetUnset($aOffset) {}

    /**
     * Removes all arguments if they are present in the token list.
     * A SyntaxError will be thrown if one of the tokens is an empty string
     * and a InvalidCharacterError will be thrown if one of the tokens contains
     * ASCII whitespace.
     * @param  string $aTokens... One or more tokens to be removed.
     */
    public function remove($aTokens) {
        if (!func_num_args()) {
            return;
        }

        $tokens = func_get_args();
        array_walk($tokens, array($this, 'checkToken'));
        $this->_remove($tokens);
        $this->update();
    }

    /**
     * Rewinds the iterator pointer to the beginning.
     */
    public function rewind() {
        $this->mPosition = 0;
    }

    /**
     * Adds or removes a token from the list based on whether or not it is
     * presently in the list.  A SyntaxError will be thrown if one of the tokens
     * is an empty string and a InvalidCharacterError will be thrown if one of
     * the tokens contains ASCII whitespace.
     * @param  string $aToken The token to be toggled.
     * @param  bool   $aForce If the token is present and $aForce is null or
     *                        false, the token is removed from the list.  If the
     *                        token is not present and $aForce is null or true,
     *                        the token is added to the list.
     * @return bool           Returns true if the token is present in the list,
     *                        otherwise false.
     */
    public function toggle($aToken, $aForce = null) {
        $contains = $this->contains($aToken);

        if ($contains) {
            if (!$aForce) {
                $this->_remove(array($aToken));
                $rv = false;
            } else {
                $rv = true;
            }
        } else {
            if ($aForce === false) {
                $rv = false;
            } else {
                $this->_add(array($aToken));
                $rv = true;
            }
        }

        $this->update();

        return $rv;
    }

    /**
     * Returns the list of tokens as a string with each token separated by a
     * space.
     * @return string Stringified token list.
     */
    public function toString() {
        return $this->serializeOrderedSet($this->mTokens);
    }

    /**
     * Checks if the iterator's current pointer points to a valid position.
     * @return bool
     */
    public function valid() {
        return isset($this->mTokens[$this->mPosition]);
    }

    /**
     * Takes an input string and then parses the string for tokens while skipping
     * over whitespace. See the following link for more info:
     * https://dom.spec.whatwg.org/#concept-ordered-set-parser
     * @param  string $aInput String of tokens.
     * @return array          Array containing the parsed tokens.
     */
    public static function _parseOrderedSet($aInput) {
        $position = 0;
        $tokens = array();
        self::collectCodePointSequence($aInput, $position, true);

        while ($position < strlen($aInput)) {
            $token = self::collectCodePointSequence($aInput, $position, false);

            if ($token && !in_array($token, $tokens)) {
                $tokens[] = $token;
            }

            self::collectCodePointSequence($aInput, $position, true);
        }

        return $tokens;
    }

    /**
     * Takes an array and concatenates the values of the array into a string
     * with each token separated by U+0020.  See the following link for more info:
     * https://dom.spec.whatwg.org/#concept-ordered-set-serializer
     * @param  array $aSet An ordered set of tokens.
     * @return string      Concatenated string of tokens.
     */
    protected function serializeOrderedSet($aSet) {
        return implode(chr(0x20), $aSet);
    }

    /**
     * Adds all tokens in the array to the token list except for those that
     * already exist in the list.
     * @param array $aTokens One or more tokens to be added to the token
     *                       list.
     */
    private function _add($aTokens) {
        foreach ($aTokens as $token) {
            if (!$this->_contains($token)) {
                $this->mTokens[] = $token;
            }
        }
    }

    /**
     * Returns true if the token is present, and false otherwise.
     * @param  string $aToken A token to check against the token list
     * @return bool           Returns true if the token is present, and false
     *                        otherwise.
     */
    private function _contains($aToken) {
        return in_array($aToken, $this->mTokens);
    }

    /**
     * Removes all tokens that are present in $aTokens if they are also present
     * in the token list.
     * @param  array $aTokens An array of tokens to be removed.
     */
    private function _remove($aTokens) {
        foreach ($aTokens as $token) {
            $key = array_search($token, $this->mTokens);
            array_splice($this->mTokens, $key, 1);
        }
    }

    /**
     * Takes a single token and checks to make sure it is not an empty string
     * and does not contain any ASCII whitespace characters.
     * @param  string $aToken A token to validate.
     */
    private function checkToken($aToken) {
        if (empty($aToken)) {
            throw new SyntaxError;
        }

        if (preg_match('/\s/', $aToken)) {
            throw new InvalidCharacterError;
        }
    }

    /**
     * Set an attribute value on the associated element using the provided attribute local name.
     */
    private function update() {
        $this->mElement->setAttribute($this->mAttrLocalName, $this->toString());
    }

    /**
     * Parses a string for a token and returns that token.  If the $aSkipWhitespace argument
     * is set, then whitespace is collected, but the caller ignores returned whitespace. See the
     * following link for more info:
     * https://dom.spec.whatwg.org/#collect-a-code-point-sequence
     * @param  string   $aInput             String of tokens to be parsed.
     * @param  int      &$aPosition         Current position in the token string.
     * @param  bool     $aSkipWhitespace    Whether to skip whitespace characters or not.
     * @return string                       Concatenated list of characters.
     */
    private static function collectCodePointSequence($aInput, &$aPosition, $aSkipWhitespace) {
        $result = '';

        while ($aPosition < strlen($aInput)) {
            if (($aSkipWhitespace && !preg_match('/\s/', $aInput[$aPosition])) ||
                !$aSkipWhitespace && preg_match('/\s/', $aInput[$aPosition])) {
                break;
            }

            $result .= $aInput[$aPosition];
            $aPosition++;
        }

        return $result;
    }
}
