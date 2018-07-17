<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Namespaces;

use function count;

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

    public function __get(string $name)
    {
        switch ($name) {
            case 'rows':
                return $this->shallowGetElementsByTagName('tr');

            default:
                return parent::__get($name);
        }
    }

    /**
     * Creates a new tr element and inserts it into the table section at
     * the specified location.  The newely created tr element is then returned.
     *
     * @param int $index The index position to insert the row at.
     *
     * @return HTMLTableRowElement
     */
    public function insertRow($index = -1)
    {
        $rows = $this->shallowGetElementsByTagName('tr');
        $numRows = count($rows);

        if ($index < -1 || $index > $numRows) {
            throw new IndexSizeError();
        }

        $tr = ElementFactory::create(
            $this->nodeDocument,
            'tr',
            Namespaces::HTML
        );

        if ($index == -1 || $index == $numRows) {
            $this->appendChild($tr);
        } else {
            $rows[$index]->before($tr);
        }

        return $tr;
    }

    /**
     * Deletes the table row at the specified location.
     *
     * @param int $index The location of the table row to be removed.
     *
     * @throws IndexSizeError If $index < 0 or $index >= number of table rows.
     */
    public function deleteRow($index)
    {
        $rows = $this->shallowGetElementsByTagName('tr');

        if ($index < 0 || $index >= count($rows)) {
            throw new IndexSizeError();
        }

        $rows[$index]->remove();
    }
}
