<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Generator;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Namespaces;

use function count;

/**
 * Represents the HTML table sectioning elements <thead>, <tfoot>, and <tbody>.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tbody-element
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-thead-element
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tfoot-element
 *
 * @property-read \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableRowElement> $rows
 */
class HTMLTableSectionElement extends HTMLElement
{
    /**
     * @var \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableRowElement>|null
     */
    private $rowsCollection;

    public function __get(string $name)
    {
        switch ($name) {
            case 'rows':
                if ($this->rowsCollection !== null) {
                    return $this->rowsCollection;
                }

                $this->rowsCollection = new HTMLCollection(
                    $this,
                    static function (self $root): Generator {
                        $node = $root->firstChild;

                        while ($node) {
                            if ($node instanceof HTMLTableRowElement) {
                                yield $node;
                            }

                            $node = $node->nextSibling;
                        }
                    }
                );

                return $this->rowsCollection;

            default:
                return parent::__get($name);
        }
    }

    /**
     * Creates a new tr element and inserts it into the table section at
     * the specified location. The newely created tr element is then returned.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError
     */
    public function insertRow(int $index = -1): HTMLTableRowElement
    {
        $rows = $this->shallowGetElementsByTagName('tr');
        $numRows = count($rows);

        if ($index < -1 || $index > $numRows) {
            throw new IndexSizeError();
        }

        $tr = ElementFactory::create($this->nodeDocument, 'tr', Namespaces::HTML);

        if ($index === -1 || $index === $numRows) {
            $this->appendChild($tr);
        } else {
            $rows[$index]->before($tr);
        }

        return $tr;
    }

    /**
     * Deletes the table row at the specified location.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If $index < 0 or $index >= number of table rows.
     */
    public function deleteRow(int $index): void
    {
        $rows = $this->shallowGetElementsByTagName('tr');

        if ($index < 0 || $index >= count($rows)) {
            throw new IndexSizeError();
        }

        $rows[$index]->remove();
    }
}
