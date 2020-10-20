<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Closure;
use Generator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Namespaces;

use function count;

/**
 * Represents the HTML table element <table>.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-table-element
 *
 * @property \Rowbot\DOM\Element\HTML\HTMLTableCaptionElement|null $caption Upon getting, it returns the first <caption>
 *                                                                          element in the table or null. Upon setting,
 *                                                                          if the value is an HTMLTableCaptionElement
 *                                                                          the first <caption> element in the table is
 *                                                                          removed and replaced with the given one. If
 *                                                                          the value is null, the first <caption>
 *                                                                          element is removed, if any.
 * @property \Rowbot\DOM\Element\HTML\HTMLTableSectionElement|null $tHead   Upon getting, it returns the first <thead>
 *                                                                          element in the table or null. Upon setting,
 *                                                                          if the value is an HTMLTableSectionElement
 *                                                                          and its tagName is THEAD or the value is
 *                                                                          null, the first <thead> element, if any, is
 *                                                                          removed from the table.  If  the value is
 *                                                                          HTMLTableSectionElement and its tagName is
 *                                                                          THEAD, the supplied value is inserted into
 *                                                                          the table before the first element that is
 *                                                                          neither a <caption>, <colgroup>, or <col>
 *                                                                          element. Throws a HierarchyRequestError if
 *                                                                          the given value is not null or
 *                                                                          HTMLTableSectionElement with a tagName of
 *                                                                          THEAD.
 * @property \Rowbot\DOM\Element\HTML\HTMLTableSectionElement|null $tFoot   Upon getting, it returns the first <tfoot>
 *                                                                          element in the table or null. Upon setting,
 *                                                                          if the value is an HTMLTableSectionElement
 *                                                                          and its tagName is TFOOT or the value is
 *                                                                          null, the first <tfoot> element, if any, is
 *                                                                          removed from the table. If the value is
 *                                                                          HTMLTableSectionElement and its tagName is
 *                                                                          TFOOT, the supplied value is inserted into
 *                                                                          the table before the first element that is
 *                                                                          neither a <caption>, <colgroup>, <col>, or
 *                                                                          <thead> element. Throws a
 *                                                                          HierarchyRequestError if the given value is
 *                                                                          not null or HTMLTableSectionElement with a
 *                                                                          tagName of TFOOT.
 *
 * @property-read \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableRowElement>     $rows    Returns a list of all the <tr>
 *                                                                                elements, in order, that are in the
 *                                                                                table.
 * @property-read \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableSectionElement> $tBodies Returns a list of all the <tbody>
 *                                                                                elements, in order, that are in the
 *                                                                                table.
 */
class HTMLTableElement extends HTMLElement
{
    /**
     * @var \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableRowElement>|null
     */
    private $rowsCollection;

    /**
     * @var \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableSectionElement>|null
     */
    private $tBodyCollection;

    public function __get(string $name)
    {
        switch ($name) {
            case 'caption':
                // The caption IDL attribute must return, on getting, the first caption element
                // child of the table element, if any, or null otherwise.
                $node = $this->childNodes->first();

                while ($node) {
                    if ($node instanceof HTMLTableCaptionElement) {
                        return $node;
                    }

                    $node = $node->nextSibling;
                }

                return null;

            case 'rows':
                if ($this->rowsCollection === null) {
                    $this->rowsCollection = new HTMLCollection($this, $this->getRowsFilter());
                }

                return $this->rowsCollection;

            case 'tBodies':
                if ($this->tBodyCollection === null) {
                    $this->tBodyCollection = new HTMLCollection(
                        $this,
                        static function (self $root) {
                            $node = $root->firstChild;

                            while ($node !== null) {
                                if (
                                    $node instanceof HTMLTableSectionElement
                                    && $node->localName === 'tbody'
                                ) {
                                    yield $node;
                                }

                                $node = $node->nextSibling;
                            }
                        }
                    );
                }

                return $this->tBodyCollection;

            case 'tFoot':
                $tfoot = $this->shallowGetElementsByTagName('tfoot');

                return $tfoot[0] ?? null;

            case 'tHead':
                // The tHead IDL attribute must return, on getting, the first thead element child of
                // the table element, if any, or null otherwise.
                $node = $this->childNodes->first();

                while ($node) {
                    if ($node instanceof HTMLTableSectionElement && $node->localName === 'thead') {
                        return $node;
                    }

                    $node = $node->nextSibling;
                }

                return null;

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'caption':
                // On setting, the first caption element child of the table element, if any, must be
                // removed, and the new value, if not null, must be inserted as the first node of
                // the table element.
                if ($value !== null && !$value instanceof HTMLTableCaptionElement) {
                    throw new TypeError();
                }

                $node = $this->childNodes->first();
                $caption = null;

                while ($node) {
                    if ($node instanceof HTMLTableCaptionElement) {
                        $caption = $node;

                        break;
                    }

                    $node = $node->nextSibling;
                }

                if ($caption) {
                    $caption->removeNode();
                }

                if ($value) {
                    $this->preinsertNode($value, $this->childNodes->first());
                }

                break;

            case 'tFoot':
                $isValid = $value === null
                    || ($value instanceof HTMLTableSectionElement && $value->tagName === 'TFOOT');

                if (!$isValid) {
                    throw new HierarchyRequestError();
                }

                $tfoot = $this->shallowGetElementsByTagName('tfoot');

                if (isset($tfoot[0])) {
                    $tFoot[0]->remove();
                }

                if ($value !== null) {
                    foreach ($this->childNodes as $node) {
                        if (
                            !$node instanceof HTMLTableCaptionElement
                            && !$node instanceof HTMLTableColElement
                            && $node->tagName === 'THEAD'
                        ) {
                            break;
                        }
                    }

                    $tfoot->insertBefore($value, $node);
                }

                break;

            case 'tHead':
                if ($value !== null && !$value instanceof HTMLTableSectionElement) {
                    throw new TypeError();
                }

                // If the new value is neither null nor a thead element, then a
                // "HierarchyRequestError" DOMException must be thrown instead.
                if ($value && $value->localName !== 'thead') {
                    throw new HierarchyRequestError();
                }

                // On setting, if the new value is null or a thead element, the first thead element
                // child of the table element, if any, must be removed,
                $node = $this->childNodes->first();

                while ($node) {
                    if ($node instanceof HTMLTableSectionElement && $node->localName === 'thead') {
                        $node->removeNode();

                        break;
                    }

                    $node = $node->nextSibling;
                }

                // and the new value, if not null, must be inserted immediately before the first
                // element in the table element that is neither a caption element nor a colgroup
                // element, if any,
                if (!$value) {
                    return;
                }

                $node = $this->childNodes->first();

                while ($node) {
                    if (
                        $node instanceof Element
                        && !$node instanceof HTMLTableColElement
                        && !$node instanceof HTMLTableCaptionElement
                    ) {
                        $this->preinsertNode($value, $node);

                        return;
                    }

                    $node = $node->nextSibling;
                }

                // or at the end of the table if there are no such elements.
                $this->preinsertNode($value);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Returns the first caption element in the table, if one exists.
     * Otherwise, it creates a new HTMLTableCaptionElement and inserts it before
     * the table's first child and returns the newly created caption element.
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-table-createcaption
     */
    public function createCaption(): HTMLTableCaptionElement
    {
        $firstChild = $this->childNodes->first();
        $node = $firstChild;

        while ($node) {
            if ($node instanceof HTMLTableCaptionElement) {
                return $node;
            }

            $node = $node->nextSibling;
        }

        $caption = ElementFactory::create($this->nodeDocument, 'caption', Namespaces::HTML);
        $this->insertNode($caption, $firstChild);

        return $caption;
    }

    /**
     * Removes the first caption element in the table, if one exists.
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-table-deletecaption
     */
    public function deleteCaption(): void
    {
        $node = $this->childNodes->first();

        while ($node) {
            if ($node instanceof HTMLTableCaptionElement) {
                $node->removeNode();

                return;
            }

            $node = $node->nextSibling;
        }
    }

    /**
     * Returns the first tfoot element in the table, if one exists.  Otherwise,
     * it creates a new HTMLTableSectionElement and inserts it before the first
     * element that is not a caption or colgroup element in the table and
     * returns the newly created tfoot element.
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-table-createthead
     */
    public function createTHead(): HTMLTableSectionElement
    {
        $firstChild = $this->childNodes->first();
        $node = $firstChild;

        while ($node) {
            if ($node instanceof HTMLTableSectionElement && $node->localName === 'thead') {
                return $node;
            }

            $node = $node->nextSibling;
        }

        $thead = ElementFactory::create($this->nodeDocument, 'thead', Namespaces::HTML);
        $node = $firstChild;

        while ($node) {
            if (
                $node instanceof Element
                && !$node instanceof HTMLTableColElement
                && !$node instanceof HTMLTableCaptionElement
            ) {
                $this->preinsertNode($thead, $node);

                return $thead;
            }

            $node = $node->nextSibling;
        }

        $this->preinsertNode($thead);

        return $thead;
    }

    /**
     * Removes the first thead element in the table, if one exists.
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-table-deletethead
     */
    public function deleteTHead(): void
    {
        $node = $this->childNodes->first();

        while ($node) {
            if ($node instanceof HTMLTableSectionElement && $node->localName === 'thead') {
                $node->removeNode();

                return;
            }

            $node = $node->nextSibling;
        }
    }

    /**
     * Returns the first tfoot element in the table, if one exists.  Otherwise,
     * it creates a new HTMLTableSectionElement and inserts it before the first
     * element that is not a caption, colgroup, or thead element in the table
     * and returns the newly created tfoot element.
     */
    public function createTFoot(): HTMLTableSectionElement
    {
        foreach ($this->childNodes as $node) {
            if (
                !$node instanceof HTMLTableCaptionElement
                && !$node instanceof HTMLTableColElement
                && $node->tagName !== 'THEAD'
            ) {
                break;
            }
        }

        return $this->createTableChildElement('tfoot', $node);
    }

    /**
     * Removes the first tfoot element in the table, if one exists.
     */
    public function deleteTFoot(): void
    {
        $this->deleteTableChildElement('tfoot');
    }

    /**
     * Creates a new HTMLTableSectionElement and inserts it after the last tbody
     * element, if one exists, otherwise it is appended to the table and returns
     * the newly created tbody element.
     */
    public function createTBody(): HTMLTableSectionElement
    {
        $tbodies = $this->shallowGetElementsByTagName('tbody');
        $len = count($tbodies);
        $lastTbody = $len ? $tbodies[$len - 1]->nextSibling : null;
        $node = $this->nodeDocument->createElement('tbody');
        $this->insertBefore($node, $lastTbody);

        return $node;
    }

    /**
     * Creates a new HTMLTableRowElement (tr), and a new HTMLTableSectionElement
     * (tbody) if one does not already exist. It then inserts the newly created
     * tr element at the specified location. It returns the newly created tr
     * element.
     *
     * @param int $index (optional) A value of -1, which is the default, is equvilant to appending the new row to the
     *                   end of the table.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If $index is < -1 or > the number of rows in the table.
     */
    public function insertRow(int $index = -1): HTMLTableRowElement
    {
        $rows = $this->rows;
        $numRows = count($rows);

        if ($index < -1 || $index > $numRows) {
            throw new IndexSizeError();
        }

        $tr = $this->nodeDocument->createElement('tr');

        if (!$numRows) {
            $tbodies = $this->shallowGetElementsByTagName('tbody');
            $numTbodies = count($tbodies);

            if (!$tbodies) {
                $tbody = $this->nodeDocument->createElement('tbody');
                $tbody->appendChild($tr);
                $this->appendChild($tbody);
            } else {
                $tbodies[$numTbodies - 1]->appendChild($tr);
            }
        } elseif ($index === -1 || $index === $numRows) {
            $rows[$numRows - 1]->parentNode->appendChild($tr);
        } else {
            $rows[$index]->before($tr);
        }

        return $tr;
    }

    /**
     * Removes the tr element at the given position.
     *
     * @param int $index A value of -1 will remove the last tr element in the table.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If $index < -1 or >= the number of tr elements in the table.
     */
    public function deleteRow(int $index): void
    {
        $rows = $this->rows;
        $numRows = count($rows);

        if ($index === -1) {
            $index = $numRows - 1;
        }

        if ($index < 0 || $index >= $numRows) {
            throw new IndexSizeError();
        }

        $rows[$index]->remove();
    }

    /**
     * Checks if an element with the specified tag name exists.  If one does not
     * exist, create a new element of the specified type, and insert it before
     * the specified element.  Then return the newely created element.
     *
     * @param string $element The tag name of the element to check against.
     *
     * @param \Rowbot\DOM\Element\HTML\HTMLElement $insertBefore The element to insert against. Null will append the
     *                                                           element to the end of the table.
     */
    private function createTableChildElement(string $element, ?HTMLElement $insertBefore): HTMLElement
    {
        $nodes = $this->shallowGetElementsByTagName($element);

        if (!isset($nodes[0])) {
            $node = $this->nodeDocument->createElement($element);
            $this->insertBefore($node, $insertBefore);
        } else {
            $node = $nodes[0];
        }

        return $node;
    }

    /**
     * Removes the first specified element found, if any.
     */
    private function deleteTableChildElement(string $element): void
    {
        $node = $this->shallowGetElementsByTagName($element);

        if (isset($node[0])) {
            $node[0]->remove();
        }
    }

    private function getRowsFilter(): Closure
    {
        return static function (self $root): Generator {
            $node = $root->firstChild;
            $bodyOrRow = [];
            $footers = [];

            while ($node) {
                if ($node instanceof HTMLTableSectionElement) {
                    $name = $node->localName;

                    if ($name === 'tbody') {
                        // Save the section for later, in order.
                        $bodyOrRow[] = $node;
                    } elseif ($name === 'tfoot') {
                        $footers[] = $node;
                    } elseif ($name === 'thead') {
                        // We're in a thead, so we can emit the rows as we find them.
                        $child = $node->firstChild;

                        while ($child) {
                            if ($child instanceof HTMLTableRowElement) {
                                yield $child;
                            }

                            $child = $child->nextSibling;
                        }
                    }
                } elseif ($node instanceof HTMLTableRowElement) {
                    $bodyOrRow[] = $node;
                }

                $node = $node->nextSibling;
            }

            foreach ($bodyOrRow as $potenialRow) {
                if ($potenialRow instanceof HTMLTableRowElement) {
                    yield $potenialRow;

                    continue;
                }

                $node = $potenialRow->firstChild;

                while ($node) {
                    if ($node instanceof HTMLTableRowElement) {
                        yield $node;
                    }

                    $node = $node->nextSibling;
                }
            }

            foreach ($footers as $footer) {
                $node = $footer->firstChild;

                while ($node) {
                    if ($node instanceof HTMLTableRowElement) {
                        yield $node;
                    }

                    $node = $node->nextSibling;
                }
            }
        };
    }
}
