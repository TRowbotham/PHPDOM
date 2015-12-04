<?php
namespace phpjs\urls;

require_once 'Host.class.php';

class IPv4Address extends Host {
    protected function __construct($aHost) {
        parent::__construct($aHost);
    }

    /**
     * Takes a string and parses it as an IPv4 address.
     *
     * @link https://url.spec.whatwg.org/#concept-ipv4-parser
     *
     * @param  string                   $aInput  A string representing an IPv4 address.
     *
     * @return IPv4Address|string|bool           Returns a IPv4Address object if the input is a valid IPv4 address
     *                                           or a string if the input is determined to be a domain.  This
     *                                           will return false if the input is neither a domain or IPv4 address.
     */
    public static function parse($aInput) {
        $syntaxViolationFlag = null;
        $parts = explode('.', $aInput);
        $len = count($parts);
        $lastIndex = $len - 1;

        if ($parts[$lastIndex] === '') {
            $syntaxViolationFlag = true;
            array_pop($parts);
        }

        if ($len > 4) {
            return $aInput;
        }

        $numbers = array();

        foreach($parts as $part) {
            if ($part === '') {
                return $aInput;
            }

            $n = self::parseIPv4Number($part, $syntaxViolationFlag);

            if ($n === false) {
                return $aInput;
            }

            $numbers[] = $n;
        }

        if ($syntaxViolationFlag) {
            // Syntax violation
        }

        foreach ($numbers as $n) {
            if ($n > 255) {
                // Syntax violation
            }
        }

        $numCount = count($numbers);

        for ($i = 0; $i < $numCount - 1; $i++) {
            if ($numbers[$i] > 255) {
                return false;
            }
        }

        if ($numbers[$numCount - 1] >= pow(256, 5 - $numCount)) {
            // Syntax violation
            return false;
        }

        $ipv4 = gmp_init(array_pop($numbers), 10);
        $counter = 0;

        foreach ($numbers as $n) {
            $ipv4 = gmp_add($ipv4, gmp_mul(gmp_init($n, 10), gmp_init(pow(256, 3 - $counter), 10)));
            $counter++;
        }

        return new IPv4Address($ipv4);
    }

    /**
     * Serializes an IPv4 address in to a string.
     *
     * @link https://url.spec.whatwg.org/#concept-ipv4-serializer
     *
     * @return string
     */
    public function serialize() {
        $output = '';
        $n = $this->mHost;

        for ($i = 0; $i < 4; $i++) {
            $output = gmp_strval(gmp_mod($n, '256')) . $output;

            if ($i < 3) {
                $output = '.' . $output;
            }

            $n = gmp_div($n, '256');
        }

        return $output;
    }

    /**
     * Takes a string and parses it as a valid IPv4 number.
     *
     * @link https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @param  string       $aInput                 A string of numbers to be parsed.
     *
     * @param  bool|null    &$aSyntaxViolationFlag  A flag that represents if there was a syntax violation
     *                                              while parsing.
     *
     * @return int|bool                             Returns a bool on failure and an int otherwise.
     */
    protected static function parseIPv4Number($aInput, &$aSyntaxViolationFlag) {
        $input = $aInput;
        $R = 10;

        if (strlen($input) > 1 && stripos($input, '0x') === 0) {
            $aSyntaxViolationFlag = true;
            $input = substr($input, 2);
            $R = 16;
        }

        if ($input === '') {
            return 0;
        } else if (strlen($input) > 1 && $input[0] === '0') {
            $syntaxViolationFlag = true;
            $input = substr($input, 1);
            $R = 8;
        }

        if (($R == 10 && !ctype_digit($input)) ||
            ($R == 16 && !ctype_xdigit($input)) ||
            ($R == 8 && decoct(octdec($input)) != $input)) {
            return false;
        }

        // TODO: Return the mathematical integer value that is represented by input in
        // radix-R notation, using ASCII hex digits for digits with values 0 through 15.
        return intval($input, $R);
    }
}
