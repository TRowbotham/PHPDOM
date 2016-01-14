<?php
namespace phpjs\urls;

class IPv6Address extends Host {
    protected function __construct($aHost) {
        parent::__construct($aHost);
    }

    /**
     * Parses an IPv6 string.
     *
     * @link https://url.spec.whatwg.org/#concept-ipv6-parser
     *
     * @param  string           $aInput [description]
     *
     * @return IPv6Address|bool         Returns an IPv6Address if the string was successfully parsed
     *                                  as an IPv6 address or false if the input is not an IPv6 address.
     */
    public static function parse($aInput) {
        $address = '0:0:0:0:0:0:0:0';
        $piecePointer = 0;
        $piece = substr($address, $piecePointer, 1);
        $compressPointer = null;
        $pointer = 0;
        $c = substr($aInput, $pointer, 1);

        if ($c == ':') {
            if (substr($aInput, $pointer + 1, 1) != ':') {
                // parse error
                return false;
            }

            $pointer += 2;
            $piecePointer++;
            $compressPointer = $piecePointer;
        }

        Main:
        while ($c !== false) {
            if ($piecePointer == 8) {
                // parse error
                return false;
            }

            if ($c == ':') {
                if ($compressPointer !== null) {
                    // parse error
                    return false;
                }

                $pointer++;
                $c = substr($aInput, $pointer, 1);
                $piecePointer++;
                $compressPointer = $piecePointer;
                goto Main;
            }

            $value = 0;
            $length = 0;

            while ($length < 4 && ctype_xdigit($c)) {
                $value = bin2hex($value * 0x10 + $c);
                $pointer++;
                $length++;
                $c = substr($aInput, $pointer, 1);
            }

            if ($c == '.') {
                if ($length == 0) {
                    // parse error
                    return false;
                }

                $pointer -= $length;
                $c = substr($aInput, $pointer, 1);
                goto IPv4;
            } elseif ($c == ':') {
                $pointer++;
                $c = substr($aInput, $pointer, 1);

                if ($c === false) {
                    // parse error
                    return false;
                }
            } elseif ($c !== false) {
                // parse error
                return false;
            }

            $piece = $value;
            $piecePointer++;
        }

        if ($c === false) {
            goto Finale;
        }

        IPv4:
        if ($piecePointer > 6) {
            // parse error
            return false;
        }

        $dotsSeen = 0;

        while ($c !== false) {
            $value = null;

            if (!ctype_xdigit($c)) {
                // parse error
                return false;
            }

            while (ctype_xdigit($c)) {
                $number = (float) $c;

                if ($value === null) {
                    $value = $number;
                } elseif ($value === 0) {
                    // parse error
                    return false;
                } else {
                    $value = $value * 10 + $number;
                }

                $pointer++;
                $c = substr($aInput, $pointer, 1);

                if ($value > 255) {
                    // parse error
                    return false;
                }
            }

            if ($dotsSeen < 3 && $c != '.') {
                // parse error
                return false;
            }

            $piece = $piece * 0x100 + $value;

            if ($dotsSeen == 1 || $dotsSeen == 3) {
                $piecePointer++;
            }

            $pointer++;
            $c = substr($aInput, $pointer, 1);

            if ($dotsSeen == 3 && $c !== false) {
                // parse error
                return false;
            }

            $dotsSeen++;
        }

        Finale:
        if ($compressPointer !== null) {
            $swaps = $piecePointer - $compressPointer;
            $piecePointer = 7;

            while ($piecePointer !== 0 && $swaps > 0) {

            }
        } elseif ($compressPointer === null && $piecePointer != 8) {
            // parse error
            return false;
        }

        return new IPv6Address($address);
    }

    /**
     * Serializes an IPv6 address.
     *
     * @link https://url.spec.whatwg.org/#concept-ipv6-serializer
     *
     * @return string
     */
    public function serialize() {
        $output = '';
        $compressPointer = null;

        return $output;
    }
}
