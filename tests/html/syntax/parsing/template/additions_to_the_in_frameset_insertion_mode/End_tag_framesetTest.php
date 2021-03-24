<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_frameset_insertion_mode;

use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-frameset-insertion-mode/end-tag-frameset.html
 */
class End_tag_framesetTest extends TestCase
{
    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testTemplateTagShouldBeIgnoredInInFramesetInsertionMode(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'frameset-end-tag.html')->contentDocument;
        // $frameset = $doc->querySelector('frameset');
        $frameset = $doc->getElementsByTagName('frameset')[0];

        self::assertSame(0, $frameset->children->length);
    }
}
