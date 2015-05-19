<?php
// https://dom.spec.whatwg.org/#interface-domsettabletokenlist

require_once 'DOMTokenList.class.php';

class DOMSettableTokenList extends DOMTokenList {
    public function __construct() {
        parent::__construct();
    }

    public function __get($aName) {
        switch ($aName) {
            case 'value':
                return $this->serializeOrderedSet($this->mTokens);

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'value':
                $this->mTokens = $this->parseOrderedSet($aValue);
        }
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
    private function collectCodePointSequence($aInput, &$aPosition, $aSkipWhitespace) {
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

    /**
     * Takes an input string and then parses the string for tokens while skipping
     * over whitespace. See the following link for more info:
     * https://dom.spec.whatwg.org/#concept-ordered-set-parser
     * @param  string $aInput String of tokens.
     * @return array          Array containing the parsed tokens.
     */
    private function parseOrderedSet($aInput) {
        $position = 0;
        $tokens = array();
        $this->collectCodePointSequence($aInput, $position, true);

        while ($position < strlen($aInput)) {
            $token = $this->collectCodePointSequence($aInput, $position, false);

            if ($token && !in_array($token, $tokens)) {
                $tokens[] = $token;
            }

            $this->collectCodePointSequence($aInput, $position, true);
        }

        return $tokens;
    }
}
