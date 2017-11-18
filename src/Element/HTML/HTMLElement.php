<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\SyntaxError;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#htmlelement
 * @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement
 *
 * @property string       $title
 * @property string       $lang
 * @property bool         $translate
 * @property string       $dir
 * @property DOMStringMap $dataset
 * @property bool         $hidden
 * @property int          $tabIndex
 * @property string       $accessKey
 * @property string       $accessKeyLabel
 * @property bool         $draggable
 * @property bool         $spellcheck
 * @property string       $contentEditable
 * @property bool         $isContentEditable
 */
class HTMLElement extends Element
{
    // state => array(keyword[, keyword, ...])
    const CONTENT_EDITABLE_STATE_MAP = [
        'true' => ['', 'true'],
        'false' => ['false']
    ];
    const CORS_STATE_MAP = [
        'Anonymous' => ['', 'anonymous'],
        'Use Credentials' => ['use-credentials']
    ];
    const DIR_STATE_MAP = [
        'ltr' => ['ltr'],
        'rtl' => ['rtl'],
        'auto' => ['auto']
    ];
    const DRAGGABLE_STATE_MAP = ['true' => ['true'], 'false' => ['false']];
    const LANG_STATE_MAP = [];
    const SPELL_CHECK_STATE_MAP = [
        'true' => ['', 'true'],
        'false' => ['false']
    ];
    const TRANSLATE_STATE_MAP = ['yes' => ['', 'yes'], 'no' => ['no']];

    protected $dataset;

    protected function __construct()
    {
        parent::__construct();

        $this->dataset;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'accessKey':
            case 'accessKeyLabel':
                // For the time being, have accessKeyLabel return the same value
                // as accessKey
                return $this->reflectStringAttributeValue('accessKey');
            case 'contentEditable':
                $state = $this->getAttributeStateEnumeratedString(
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
                return $this->getAttributeStateEnumeratedString(
                    $name,
                    null,
                    null,
                    self::DIR_STATE_MAP
                );
            case 'draggable':
                $state = $this->getAttributeStateEnumeratedString(
                    $name,
                    null,
                    'auto',
                    self::DRAGGABLE_STATE_MAP
                );

                return $state == 'true' ? true : false;
            case 'dropzone':
                return $this->reflectStringAttributeValue($name);
            case 'hidden':
                return $this->reflectBooleanAttributeValue($name);
            case 'isContentEditable':
                $state = $this->getAttributeStateEnumeratedString(
                    $name,
                    'inherit',
                    'inherit',
                    self::CONTENT_EDITABLE_STATE_MAP
                );
                // TODO: Check the contentEditable state of all parent elements
                // if state == inherit to get a more accurate answer
                return $state == 'true' ? true : false;
            case 'lang':
                return $this->reflectStringAttributeValue($name);
            case 'spellcheck':
                $state = $this->getAttributeStateEnumeratedString(
                    $name,
                    'default',
                    'default',
                    self::SPELL_CHECK_STATE_MAP
                );

                if ($state == 'true') {
                    $value = true;
                } elseif ($state == 'false') {
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
                $state = $this->getAttributeStateEnumeratedString(
                    $name,
                    'inherit',
                    'inherit',
                    self::TRANSLATE_STATE_MAP
                );
                // TODO: Check the translate state of all parent elements to get
                // a more accurate answer
                return $state == 'yes' ? true : false;
            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'accessKey':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'contentEditable':
                if (strcasecmp($value, 'inherit') === 0) {
                    $this->removeAttrByNamespaceAndLocalName(
                        null,
                        $name,
                        $this
                    );
                } elseif (
                    strcasecmp($value, 'true') === 0 ||
                    strcasecmp($value, 'false') === 0
                ) {
                    $this->attributeList->setAttrValue($name, $value);
                } else {
                    throw new SyntaxError(
                        'The value must be one of "true", "false", or
                        "inherit".'
                    );
                }

                break;

            case 'dir':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'draggable':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'dropzone':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'hidden':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'lang':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'spellcheck':
                $this->attributeList->setAttrValue(
                    $name,
                    ($value === true ? 'true' : 'false')
                );

                break;

            case 'tabIndex':
                $this->attributeList->setAttrValue(
                    'tabindex',
                    $value
                );

                break;

            case 'title':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'translate':
                $this->attributeList->setAttrValue(
                    $name,
                    ($value === true ? 'yes' : 'no')
                );

                break;

            default:
                parent::__set($name, $value);
        }
    }

    protected function reflectBooleanAttributeValue($name)
    {
        return (bool) $this->attributeList->getAttrByNamespaceAndLocalName(
            null,
            $name
        );
    }

    protected function reflectEnumeratedStringAttributeValue(
        $name,
        $missingValueDefault = null
    ) {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName(
            null,
            $name
        );

        if (!$attr && $missingValueDefault !== null) {
            return null;
        }

        return $attr ? $attr->value : '';
    }

    protected function getAttributeStateEnumeratedString(
        $name,
        $invalidValueDefault = null,
        $missingValueDefault = null,
        array $stateMap = array()
    ) {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName(
            null,
            $name
        );
        $state = null;

        if ($attr) {
            foreach ($stateMap as $attributeState => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strcasecmp($attr->value, $keyword) === 0) {
                        $state = $attributeState;
                        break 2;
                    }
                }
            }

            if ($state === null) {
                if ($invalidValueDefault !== null) {
                    $state = $invalidValueDefault;
                } elseif ($missingValueDefault !== null) {
                    $state = $missingValueDefault;
                }
            }
        } elseif (!$attr && $missingValueDefault !== null) {
            $state = $missingValueDefault;
        }

        return $state !== null ? $state : '';
    }
}
