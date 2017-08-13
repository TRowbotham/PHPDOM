<?php
namespace Rowbot\DOM\Element\HTML;

/**
 * Represents the HTML table header element <th>.
 *
 * @see https://html.spec.whatwg.org/#the-th-element
 *
 * @property string $abbr An alternative label to use for the header cell when
 *     referencing the cell in other contexts.  This propterty reflects the
 *     value of the abbr attribute.
 *
 * @property string $scope Specifies which cells the header cell applies to.
 *     The only accpeted values are row, col, rowgroup, and colgroup.  This
 *     property reflects the value of the scope attribute.
 *
 * @property string $sorted Column sort direction and its ordinality.  This
 *     property reflects the value of the sorted attribute.
 */
class HTMLTableHeaderCellElement extends HTMLTableCellElement
{
    private $mAbbr;
    private $mScope;
    private $mSorted;

    protected function __construct()
    {
        parent::__construct();

        $this->mAbbr = '';
        $this->mScope = '';
        $this->mSorted = '';
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'abbr':
                return $this->mAbbr;

            case 'scope':
                return $this->mScope;

            case 'sorted':
                return $this->mSorted;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'abbr':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mAbbr = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'scope':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mScope = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'sorted':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mScope = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function sort()
    {
        // TODO
    }
}
