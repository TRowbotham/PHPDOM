<?php
require_once 'HTMLTableCellElement.class.php';

/**
 * Represents the HTML table cell element <td>.
 *
 * @link https://html.spec.whatwg.org/#the-td-element
 */
class HTMLTableDataCellElement extends HTMLTableCellElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }
}
