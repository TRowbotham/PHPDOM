<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Document;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createProcessingInstruction-xhtml.xhtml
 */
class Document_createProcessingInstructionXhtmlTest extends NodeTestCase
{
    use Document_createProcessingInstructionTrait;

    public function getDocument(): Document
    {
        return new Document();
    }
}
