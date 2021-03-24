<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\resources;

use Rowbot\DOM\HTMLDocument;

use function in_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/resources/common.js
 */
trait CommonTrait
{
    private static $HTML5_ELEMENTS = [ 'a', 'abbr', 'address', 'area', 'article', 'aside',
        'audio', 'b', 'base', 'bdi', 'bdo', 'blockquote', 'body', 'br',
        'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup',
        'command', 'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'div',
        'dl', 'dt', 'em', 'embed', 'fieldset', 'figcaption', 'figure',
        'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header',
        'hgroup', 'hr', 'html', 'i', 'iframe', 'img', 'input', 'ins', 'kbd',
        'keygen', 'label', 'legend', 'li', 'link', 'map', 'mark', 'menu',
        'meta', 'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup',
        'option', 'output', 'p', 'param', 'pre', 'progress', 'q', 'rp', 'rt',
        'ruby', 's', 'samp', 'script', 'section', 'select', 'small', 'source',
        'span', 'strong', 'style', 'sub', 'table', 'tbody', 'td', 'textarea',
        'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul',
        'var', 'video', 'wbr' ];

    private static $HTML5_VOID_ELEMENTS = [ 'area', 'base', 'br', 'col', 'command', 'embed',
        'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source',
        'track', 'wbr' ];

    protected function newHTMLDocument(): HTMLDocument
    {
        return (new HTMLDocument())->implementation->createHTMLDocument('Test Document');
    }

    protected function isVoidElement(string $elementName): bool
    {
        return in_array($elementName, self::$HTML5_VOID_ELEMENTS, true);
    }
}
