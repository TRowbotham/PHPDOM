<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\serializing_html_fragments;

use PHPUnit\Framework\Attributes\DataProvider;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\html\syntax\Html_element_list_trait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/serializing-html-fragments/outerHTML.html
 */
class OuterHTMLTest extends TestCase
{
    use Html_element_list_trait;

    #[DataProvider('elementsWithEndTagProvider')]
    public function testElementsWithEndTag(string $ele): void
    {
        $e = (new HTMLDocument())->createElement($ele);
        self::assertSame('<' . $ele . '></' . $ele . '>', $e->outerHTML);
    }

    #[DataProvider('elementsWithoutEndTagProvider')]
    public function testElementsWithoutEndTag(string $ele): void
    {
        $e = (new HTMLDocument())->createElement($ele);
        self::assertSame('<' . $ele . '>', $e->outerHTML);
    }
}
