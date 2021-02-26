<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Utils;

use function assert;
use function filter_var;
use function is_numeric;
use function mb_strtolower;

use const FILTER_VALIDATE_INT;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#htmlelement
 * @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement
 *
 * @property string                   $title
 * @property string                   $lang
 * @property bool                     $translate
 * @property string                   $dir
 * @property \Rowbot\DOM\DOMStringMap $dataset
 * @property bool                     $hidden
 * @property int                      $tabIndex
 * @property string                   $accessKey
 * @property string                   $accessKeyLabel
 * @property bool                     $draggable
 * @property bool                     $spellcheck
 * @property string                   $contentEditable
 * @property bool                     $isContentEditable
 */
class HTMLElement extends Element
{
    // state => array(keyword[, keyword, ...])
    protected const CONTENT_EDITABLE_STATE_MAP = [
        'true' => ['', 'true'],
        'false' => ['false'],
    ];
    protected const CORS_STATE_MAP = [
        'Anonymous' => ['', 'canonical' => 'anonymous'],
        'Use Credentials' => ['use-credentials'],
    ];
    protected const DIR_STATE_MAP = [
        'ltr' => ['ltr'],
        'rtl' => ['rtl'],
        'auto' => ['auto'],
    ];
    protected const DRAGGABLE_STATE_MAP = ['true' => ['true'], 'false' => ['false']];
    protected const LANG_STATE_MAP = [];
    protected const SPELL_CHECK_STATE_MAP = [
        'true' => ['', 'true'],
        'false' => ['false'],
    ];
    protected const TRANSLATE_STATE_MAP = ['yes' => ['', 'yes'], 'no' => ['no']];

    protected const MAX_SIGNED_LONG = 2147483647;

    protected const SIGNED_LONG = 1;
    protected const SIGNED_LONG_NON_NEGATIVE = 2;
    protected const UNSIGNED_LONG = 3;
    protected const UNSIGNED_LONG_NON_NEGATIVE_GREATER_THAN_ZERO = 4;
    protected const UNSIGNED_LONG_NON_NEGATIVE_GREATER_THAN_ZERO_WITH_FALLBACK = 5;

    protected $dataset;

    public function __get(string $name)
    {
        switch ($name) {
            case 'accessKey':
            case 'accessKeyLabel':
                // For the time being, have accessKeyLabel return the same value
                // as accessKey
                return $this->reflectStringAttributeValue('accessKey');

            case 'contentEditable':
                $state = $this->reflectEnumeratedStringAttributeValue(
                    $name,
                    'inherit',
                    'inherit',
                    self::CONTENT_EDITABLE_STATE_MAP
                );

                // TODO: Check the contentEditable state of all parent elements
                // if state == inherit to get a more accurate answer
                return $state;

            case 'dataset':
                return $this->dataset;

            case 'dir':
                return $this->reflectEnumeratedStringAttributeValue(
                    $name,
                    null,
                    null,
                    self::DIR_STATE_MAP
                );

            case 'draggable':
                $state = $this->reflectEnumeratedStringAttributeValue(
                    $name,
                    null,
                    'auto',
                    self::DRAGGABLE_STATE_MAP
                );

                return $state === 'true' ? true : false;

            case 'dropzone':
                return $this->reflectStringAttributeValue($name);

            case 'hidden':
                return $this->reflectBooleanAttributeValue($name);

            case 'isContentEditable':
                $state = $this->reflectEnumeratedStringAttributeValue(
                    $name,
                    'inherit',
                    'inherit',
                    self::CONTENT_EDITABLE_STATE_MAP
                );

                // TODO: Check the contentEditable state of all parent elements
                // if state == inherit to get a more accurate answer
                return $state === 'true' ? true : false;

            case 'lang':
                return $this->reflectStringAttributeValue($name);

            case 'spellcheck':
                $state = $this->reflectEnumeratedStringAttributeValue(
                    $name,
                    'default',
                    'default',
                    self::SPELL_CHECK_STATE_MAP
                );

                if ($state === 'true') {
                    $value = true;
                } elseif ($state === 'false') {
                    $value = false;
                } else {
                    // TODO: Handle default states
                    return false;
                }

                return $value;

            case 'tabIndex':
                $index = filter_var(
                    $this->reflectStringAttributeValue('tabindex'),
                    FILTER_VALIDATE_INT,
                    ['default' => 0]
                );

                return $index;

            case 'title':
                return $this->reflectStringAttributeValue($name);

            case 'translate':
                $state = $this->reflectEnumeratedStringAttributeValue(
                    $name,
                    'inherit',
                    'inherit',
                    self::TRANSLATE_STATE_MAP
                );

                // TODO: Check the translate state of all parent elements to get
                // a more accurate answer
                return $state === 'yes' ? true : false;

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'accessKey':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'contentEditable':
                $value = mb_strtolower((string) $value, 'utf-8');

                if ($value === 'inherit') {
                    $this->attributeList->removeAttrByNamespaceAndLocalName(null, $name);
                } elseif ($value === 'true' || $value === 'false') {
                    $this->attributeList->setAttrValue($name, $value);
                } else {
                    throw new SyntaxError(
                        'The value must be one of "true", "false", or "inherit".'
                    );
                }

                break;

            case 'dir':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'draggable':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'dropzone':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'hidden':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'lang':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'spellcheck':
                $this->attributeList->setAttrValue($name, ($value === true ? 'true' : 'false'));

                break;

            case 'tabIndex':
                $this->attributeList->setAttrValue('tabindex', (string) $value);

                break;

            case 'title':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'translate':
                $this->attributeList->setAttrValue($name, ($value === true ? 'yes' : 'no'));

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * If the attribute is present, its value must either be the empty string or a value that is an
     * ASCII case-insensitive match for the attribute's canonical name, with no leading or trailing
     * whitespace.
     *
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#reflecting-content-attributes-in-idl-attributes
     * @see https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#boolean-attributes
     */
    protected function reflectBooleanAttributeValue(string $name, ?string $namespace = null): bool
    {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName($namespace, $name);

        if (!$attr) {
            return false;
        }

        $value = $attr->getValue();

        return $value === '' || $attr->getLocalName() === Utils::toASCIILowercase($value);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#reflecting-content-attributes-in-idl-attributes
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#limited-to-only-non-negative-numbers
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#limited-to-only-non-negative-numbers-greater-than-zero
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#limited-to-only-non-negative-numbers-greater-than-zero-with-fallback
     */
    protected function reflectLongAttributeValue(
        string $name,
        int $mode,
        int $default = null,
        ?string $namespace = null
    ): int {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName($namespace, $name);

        switch ($mode) {
            case self::SIGNED_LONG:
                // If a reflecting IDL attribute has a signed integer type (long) then, on getting,
                // the content attribute must be parsed according to the rules for parsing signed
                // integers, and if that is successful, and the value is in the range of the IDL
                // attribute's type, the resulting value must be returned. If, on the other hand, it
                // fails or returns an out of range value, or if the attribute is absent, then the
                // default value must be returned instead, or 0 if there is no default value.
                $default = $default ?? 0;
                $options = ['min_range' => ~self::MAX_SIGNED_LONG];

                break;

            case self::SIGNED_LONG_NON_NEGATIVE:
                // If a reflecting IDL attribute has a signed integer type (long) that is limited to
                // only non-negative numbers then, on getting, the content attribute must be parsed
                // according to the rules for parsing non-negative integers, and if that is
                // successful, and the value is in the range of the IDL attribute's type, the
                // resulting value must be returned. If, on the other hand, it fails or returns an
                // out of range value, or if the attribute is absent, the default value must be
                // returned instead, or −1 if there is no default value.
                $default = $default ?? -1;
                $options = ['min_range' => 0];

                break;

            case self::UNSIGNED_LONG:
                // If a reflecting IDL attribute has an unsigned integer type (unsigned long) then,
                // on getting, the content attribute must be parsed according to the rules for
                // parsing non-negative integers, and if that is successful, and the value is in the
                // range 0 to 2147483647 inclusive, the resulting value must be returned. If, on the
                // other hand, it fails or returns an out of range value, or if the attribute is
                // absent, the default value must be returned instead, or 0 if there is no default
                // value.
                $default = $default ?? 0;
                $options = ['min_range' => 0];

                break;

            case self::UNSIGNED_LONG_NON_NEGATIVE_GREATER_THAN_ZERO_WITH_FALLBACK:
                // If a reflecting IDL attribute has an unsigned integer type (unsigned long) that
                // is limited to only non-negative numbers greater than zero with fallback, then the
                // behavior is similar to the previous case, but disallowed values are converted to
                // the default value. On getting, the content attribute must first be parsed
                // according to the rules for parsing non-negative integers, and if that is
                // successful, and the value is in the range 1 to 2147483647 inclusive, the
                // resulting value must be returned. If, on the other hand, it fails or returns an
                // out of range value, or if the attribute is absent, the default value must be
                // returned instead.
                assert($default !== null);

                // no break

            case self::UNSIGNED_LONG_NON_NEGATIVE_GREATER_THAN_ZERO:
                // If a reflecting IDL attribute has an unsigned integer type (unsigned long) that
                // is limited to only non-negative numbers greater than zero, then the behavior is
                // similar to the previous case, but zero is not allowed. On getting, the content
                // attribute must first be parsed according to the rules for parsing non-negative
                // integers, and if that is successful, and the value is in the range 1 to
                // 2147483647 inclusive, the resulting value must be returned. If, on the other
                // hand, it fails or returns an out of range value, or if the attribute is absent,
                // the default value must be returned instead, or 1 if there is no default value.
                $default = $default ?? 1;
                $options = ['min_range' => 1];

                break;
        }

        $options['max_range'] = self::MAX_SIGNED_LONG;
        $options['default'] = $default;

        if (!$attr) {
            return $default;
        }

        return filter_var($attr->getValue(), FILTER_VALIDATE_INT, ['options' => $options]);
    }

    /**
     * If a reflecting IDL attribute has an unsigned integer type (unsigned long) that is clamped to
     * the range [min, max], then on getting, the content attribute must first be parsed according
     * to the rules for parsing non-negative integers, and if that is successful, and the value is
     * between min and max inclusive, the resulting value must be returned. If it fails, the
     * default value must be returned. If it succeeds but the value is less than min, min must be
     * returned. If it succeeds but the value is greater than max, max must be returned. On setting,
     * it behaves the same as setting a regular reflected unsigned integer.
     *
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#reflecting-content-attributes-in-idl-attributes
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#clamped-to-the-range
     */
    protected function reflectClampedUnsignedLongAttributeValue(
        string $name,
        int $min,
        int $max,
        int $default,
        ?string $namespace = null
    ): int {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName($namespace, $name);

        if (!$attr) {
            return $default;
        }

        $value = $attr->getValue();

        if (!is_numeric($value)) {
            return $default;
        }

        $value = (int) $value;

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }

    /**
     * If a reflecting IDL attribute is a DOMString attribute whose content attribute is an
     * enumerated attribute, and the IDL attribute is limited to only known values, then, on
     * getting, the IDL attribute must return the keyword value associated with the state the
     * attribute is in, if any, or the empty string if the attribute is in a state that has no
     * associated keyword value or if the attribute is not in a defined state (e.g. the attribute is
     * missing and there is no missing value default). If there are multiple keyword values for the
     * state, then return the conforming one. If there are multiple conforming keyword values, then
     * one will be designated the canonical keyword; choose that one.
     *
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#reflecting-content-attributes-in-idl-attributes:enumerated-attribute
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#limited-to-only-known-values
     *
     * @param array<string, array<int|string, string>> $stateMap
     */
    protected function reflectEnumeratedStringAttributeValue(
        string $name,
        string $invalidValueDefault = null,
        string $missingValueDefault = null,
        array $stateMap = [],
        ?string $namespace = null
    ): string {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName($namespace, $name);

        if (!$attr) {
            if ($missingValueDefault !== null) {
                return $missingValueDefault;
            }

            return '';
        }

        $value = Utils::toASCIILowercase($attr->getValue());

        foreach ($stateMap as $keywords) {
            foreach ($keywords as $keyword) {
                if ($value === $keyword) {
                    return $keywords['canonical'] ?? $keyword;
                }
            }
        }

        if ($invalidValueDefault !== null) {
            return $invalidValueDefault;
        }

        return '';
    }

    /**
     * On setting, the content attribute must be removed if the IDL attribute is set to false, and
     * must be set to the empty string if the IDL attribute is set to true. (This corresponds to the
     * rules for boolean content attributes.)
     *
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#reflecting-content-attributes-in-idl-attributes
     *
     * @param mixed $newValue
     */
    protected function setBooleanAttributeValue(
        string $name,
        $newValue,
        ?string $prefix = null,
        ?string $namespace = null
    ): void {
        if (!$newValue) {
            $this->attributeList->removeAttrByNamespaceAndLocalName($namespace, $name);

            return;
        }

        $this->attributeList->setAttrValue($name, '', $prefix, $namespace);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#reflecting-content-attributes-in-idl-attributes
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#limited-to-only-non-negative-numbers
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#limited-to-only-non-negative-numbers-greater-than-zero
     * @see https://html.spec.whatwg.org/multipage/common-dom-interfaces.html#limited-to-only-non-negative-numbers-greater-than-zero-with-fallback
     *
     * @param mixed $newValue
     */
    protected function setLongAttributeValue(
        string $name,
        $newValue,
        int $mode,
        int $default = null,
        ?string $prefix = null,
        ?string $namespace = null
    ): void {
        $value = filter_var($newValue, FILTER_VALIDATE_INT);

        if ($value === false) {
            // filter_var() will return false on integer overflow.
            throw new DOMException(
                'Either the value could not be converted to an integer or integer overflow occured.'
            );
        }

        switch ($mode) {
            case self::SIGNED_LONG:
                // On setting, the given value must be converted to the shortest possible string
                // representing the number as a valid integer and then that string must be used as
                // the new content attribute value.
                $n = $value;

                break;

            case self::SIGNED_LONG_NON_NEGATIVE:
                // On setting, if the value is negative, the user agent must throw an
                // "IndexSizeError" DOMException. Otherwise, the given value must be converted to
                // the shortest possible string representing the number as a valid non-negative
                // integer and then that string must be used as the new content attribute value.
                if ($value < 0) {
                    throw new IndexSizeError('The given value must not be negative.');
                }

                $n = $value;

                break;

            case self::UNSIGNED_LONG:
                // On setting, first, if the new value is in the range 0 to 2147483647, then let n
                // be the new value, otherwise let n be the default value, or 0 if there is no default
                // value; then, n must be converted to the shortest possible string representing the
                // number as a valid non-negative integer and that string must be used as the new
                // content attribute value.
                $n = $value;

                if ($value < 0 || $value > self::MAX_SIGNED_LONG) {
                    $n = $default ?? 0;
                }

                break;

            case self::UNSIGNED_LONG_NON_NEGATIVE_GREATER_THAN_ZERO:
                // On setting, if the value is zero, the user agent must throw an "IndexSizeError"
                // DOMException. Otherwise, first, if the new value is in the range 1 to 2147483647,
                // then let n be the new value, otherwise let n be the default value, or 1 if there
                // is no default value; then, n must be converted to the shortest possible string
                // representing the number as a valid non-negative integer and that string must be
                // used as the new content attribute value.
                if ($value === 0) {
                    throw new IndexSizeError('The given value must not be 0.');
                }

                $n = $value;

                if ($value < 1 || $value > self::MAX_SIGNED_LONG) {
                    $n = $default ?? 1;
                }

                break;

            case self::UNSIGNED_LONG_NON_NEGATIVE_GREATER_THAN_ZERO_WITH_FALLBACK:
                // On setting, first, if the new value is in the range 1 to 2147483647, then let n
                // be the new value, otherwise let n be the default value; then, n must be converted
                // to the shortest possible string representing the number as a valid non-negative
                // integer and that string must be used as the new content attribute value.
                assert($default !== null);
                $n = $value;

                if ($value < 1 || $value > self::MAX_SIGNED_LONG) {
                    $n = $default;
                }

                break;
        }

        $this->attributeList->setAttrValue($name, (string) $n, $prefix, $namespace);
    }
}
