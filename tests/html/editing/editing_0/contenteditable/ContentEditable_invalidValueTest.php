<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\editing\editing_0\contenteditable;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/editing/editing-0/contenteditable/contentEditable-invalidvalue.html
 */
class ContentEditable_invalidValueTest extends TestCase
{
    public function testInvalidValue(): void
    {
        $el = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument()->createElement('div');
        $this->expectException(SyntaxError::class);

        $el->contentEditable = 'foobar';
    }
}
