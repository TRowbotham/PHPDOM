<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\Exception\NoModificationAllowedError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/outerhtml-01.html
 */
class OuterHTML01Test extends TestCase
{
    public function testOuterHTMLAndStringConversionNull(): void
    {
        $document = new HTMLDocument();
        $p = $document->createElement('p');
        $this->expectException(NoModificationAllowedError::class);
        $p->outerHTML = '<html><p>FAIL: Should have thrown an error<\/p><\/html>';
    }
}
