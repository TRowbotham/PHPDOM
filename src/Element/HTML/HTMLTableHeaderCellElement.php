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
    private $abbr;
    private $scope;
    private $sorted;

    protected function __construct()
    {
        parent::__construct();

        $this->abbr = '';
        $this->scope = '';
        $this->sorted = '';
    }

    public function __get($name)
    {
        switch ($name) {
            case 'abbr':
                return $this->abbr;

            case 'scope':
                return $this->scope;

            case 'sorted':
                return $this->sorted;

            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'abbr':
                if (!\is_string($value)) {
                    break;
                }

                $this->abbr = $value;
                $this->_updateAttributeOnPropertyChange($name, $value);

                break;

            case 'scope':
                if (!\is_string($value)) {
                    break;
                }

                $this->scope = $value;
                $this->_updateAttributeOnPropertyChange($name, $value);

                break;

            case 'sorted':
                if (!\is_string($value)) {
                    break;
                }

                $this->scope = $value;
                $this->_updateAttributeOnPropertyChange($name, $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
