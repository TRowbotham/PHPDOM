<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createProcessingInstruction.html
 */
class DocumentCreateProcessingInstructionTest extends NodeTestCase
{
    use Document_createProcessingInstructionTrait;

    public function getDocument(): HTMLDocument
    {
        return new HTMLDocument();
    }
}
