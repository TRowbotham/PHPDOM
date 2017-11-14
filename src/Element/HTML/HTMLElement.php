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

    protected $mDataset;

    protected function __construct()
    {
        parent::__construct();

        $this->mDataset;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'accessKey':
            case 'accessKeyLabel':
                // For the time being, have accessKeyLabel return the same value
                // as accessKey
                return $this->reflectStringAttributeValue('accessKey');
            case 'contentEditable':
                $state = $this->getAttributeStateEnumeratedString(
                    $aName,
                    'inherit',
                    'inherit',
                    self::CONTENT_EDITABLE_STATE_MAP
                );
                // TODO: Check the contentEditable state of all parent elements
                // if state == inherit to get a more accurate answer
                return $state;
            case 'dataset':
                return $this->mDataset;
            case 'dir':
                return $this->getAttributeStateEnumeratedString(
                    $aName,
                    null,
                    null,
                    self::DIR_STATE_MAP
                );
            case 'draggable':
                $state = $this->getAttributeStateEnumeratedString(
                    $aName,
                    null,
                    'auto',
                    self::DRAGGABLE_STATE_MAP
                );

                return $state == 'true' ? true : false;
            case 'dropzone':
                return $this->reflectStringAttributeValue($aName);
            case 'hidden':
                return $this->reflectBooleanAttributeValue($aName);
            case 'isContentEditable':
                $state = $this->getAttributeStateEnumeratedString(
                    $aName,
                    'inherit',
                    'inherit',
                    self::CONTENT_EDITABLE_STATE_MAP
                );
                // TODO: Check the contentEditable state of all parent elements
                // if state == inherit to get a more accurate answer
                return $state == 'true' ? true : false;
            case 'lang':
                return $this->reflectStringAttributeValue($aName);
            case 'spellcheck':
                $state = $this->getAttributeStateEnumeratedString(
                    $aName,
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
                return $this->reflectStringAttributeValue($aName);
            case 'translate':
                $state = $this->getAttributeStateEnumeratedString(
                    $aName,
                    'inherit',
                    'inherit',
                    self::TRANSLATE_STATE_MAP
                );
                // TODO: Check the translate state of all parent elements to get
                // a more accurate answer
                return $state == 'yes' ? true : false;
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'accessKey':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'contentEditable':
                if (strcasecmp($aValue, 'inherit') === 0) {
                    $this->removeAttrByNamespaceAndLocalName(
                        null,
                        $aName,
                        $this
                    );
                } elseif (
                    strcasecmp($aValue, 'true') === 0 ||
                    strcasecmp($aValue, 'false') === 0
                ) {
                    $this->mAttributesList->setAttrValue($aName, $aValue);
                } else {
                    throw new SyntaxError(
                        'The value must be one of "true", "false", or
                        "inherit".'
                    );
                }

                break;

            case 'dir':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'draggable':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'dropzone':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'hidden':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'lang':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'spellcheck':
                $this->mAttributesList->setAttrValue(
                    $aName,
                    ($aValue === true ? 'true' : 'false')
                );

                break;

            case 'tabIndex':
                $this->mAttributesList->setAttrValue(
                    'tabindex',
                    $aValue
                );

                break;

            case 'title':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'translate':
                $this->mAttributesList->setAttrValue(
                    $aName,
                    ($aValue === true ? 'yes' : 'no')
                );

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    protected function reflectBooleanAttributeValue($aName)
    {
        return (bool) $this->mAttributesList->getAttrByNamespaceAndLocalName(
            null,
            $aName
        );
    }

    protected function reflectEnumeratedStringAttributeValue(
        $aName,
        $aMissingValueDefault = null
    ) {
        $attr = $this->mAttributesList->getAttrByNamespaceAndLocalName(
            null,
            $aName
        );

        if (!$attr && $aMissingValueDefault !== null) {
            return null;
        }

        return $attr ? $attr->value : '';
    }

    protected function getAttributeStateEnumeratedString(
        $aName,
        $aInvalidValueDefault = null,
        $aMissingValueDefault = null,
        array $aStateMap = array()
    ) {
        $attr = $this->mAttributesList->getAttrByNamespaceAndLocalName(
            null,
            $aName
        );
        $state = null;

        if ($attr) {
            foreach ($aStateMap as $attributeState => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strcasecmp($attr->value, $keyword) === 0) {
                        $state = $attributeState;
                        break 2;
                    }
                }
            }

            if ($state === null) {
                if ($aInvalidValueDefault !== null) {
                    $state = $aInvalidValueDefault;
                } elseif ($aMissingValueDefault !== null) {
                    $state = $aMissingValueDefault;
                }
            }
        } elseif (!$attr && $aMissingValueDefault !== null) {
            $state = $aMissingValueDefault;
        }

        return $state !== null ? $state : '';
    }
}
