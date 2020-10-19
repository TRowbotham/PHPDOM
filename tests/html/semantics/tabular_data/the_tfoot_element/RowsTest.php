<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_tfoot_element;

use Rowbot\DOM\Tests\html\semantics\tabular_data\HtmlTableSectionElementTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-tfoot-element/rows.html
 */
class RowsTest extends TestCase
{
    use HtmlTableSectionElementTrait;

    public function getTableSectionName(): string
    {
        return 'tfoot';
    }
}
