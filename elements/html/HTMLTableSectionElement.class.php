<?php
namespace phpjs\elements\html;

use phpjs\exceptions\IndexSizeError;

/**
 * Represents the HTML table sectioning elements <thead>, <tfoot>, and <tbody>.
 *
 * @see https://html.spec.whatwg.org/#the-tbody-element
 * @see https://html.spec.whatwg.org/#the-thead-element
 * @see https://html.spec.whatwg.org/#the-tfoot-element
 *
 * @property HTMLTableRowElement[] $rows Returns all of the <tr> elements within
 *     this section element.
 */
class HTMLTableSectionElement extends HTMLElement
{
    protected function __construct()
    {
        parent::__construct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'rows':
                return $this->shallowGetElementsByTagName('tr');

            default:
                return parent::__get($aName);
        }
    }

    /**
     * Creates a new tr element and inserts it into the table section at
     * the specified location.  The newely created tr element is then returned.
     *
     * @param int $aIndex The index position to insert the row at.
     *
     * @return HTMLTableRowElement
     */
    public function insertRow($aIndex = -1)
    {
        $rows = $this->shallowGetElementsByTagName('tr');
        $numRows = count($rows);

        if ($aIndex < -1 || $aIndex > $numRows) {
            throw new IndexSizeError();
        }

        $tr = $this->mOwnerDocument->createElement('tr');

        if ($aIndex == -1 || $aIndex == $numRows) {
            $this->appendChild($tr);
        } else {
            $rows[$aIndex]->before($tr);
        }

        return $tr;
    }

    /**
     * Deletes the table row at the specified location.
     *
     * @param int $aIndex The location of the table row to be removed.
     *
     * @throws IndexSizeError If $aIndex < 0 or $aIndex >= number of table rows.
     */
    public function deleteRow($aIndex)
    {
        $rows = $this->shallowGetElementsByTagName('tr');

        if ($aIndex < 0 || $aIndex >= count($rows)) {
            throw new IndexSizeError();
        }

        $rows[$aIndex]->remove();
    }
}
