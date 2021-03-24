<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Generator;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;
use RuntimeException;

use function file_get_contents;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;

use const DIRECTORY_SEPARATOR as DS;
use const JSON_ERROR_NONE;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/named-character-references.html
 */
class Named_character_referencesTest extends TestCase
{
    /**
     * @dataProvider entityProvider
     */
    public function testCharacterReference(string $entity, string $characters): void
    {
        $dummy = (new HTMLDocument())->createElement('p');
        $dummy->innerHTML = $entity;

        self::assertSame($characters, $dummy->textContent);
    }

    public function entityProvider(): Generator
    {
        $data = file_get_contents(__DIR__ . DS . 'resources' . DS . 'named-character-references-data.json');

        if ($data === false) {
            throw new RuntimeException('Could not read named-character-references.json');
        }

        $data = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode json file: ' . json_last_error_msg());
        }

        foreach ($data as $entity => $info) {
            yield [$entity, $info['characters']];
        }
    }
}
