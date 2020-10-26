<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\Exception\NoModificationAllowedError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/outerhtml-01.html
 */
class OuterHTML01Test extends TestCase
{
    use DocumentGetter;

    public function testOuterHTMLAndStringConversionNull(): void
    {
        $document = $this->getHTMLDocument();
        $this->expectException(NoModificationAllowedError::class);
        $document->documentElement->outerHTML = '<html><p>FAIL: Should have thrown an error<\/p><\/html>';
    }
}
