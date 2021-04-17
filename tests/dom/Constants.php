<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom;

use ReflectionClass;

use function sprintf;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/constants.js
 */
trait Constants
{
    abstract public function constantsProvider(): array;

    /**
     * @dataProvider constantsProvider
     */
    public function testConstants($objects, $constants): void
    {
        foreach ($objects as $object) {
            $reflection = new ReflectionClass($object[0]);

            foreach ($constants as $constant) {
                $this->assertTrue($reflection->hasConstant($constant[0]), sprintf(
                    'Object "%s" doesn\'t have "%s".',
                    $reflection->getName(),
                    $constant[0]
                ));
                $this->assertSame($constant[1], $reflection->getConstant($constant[0]), sprintf(
                    'Object "%s" value for "%s" is wrong.',
                    $reflection->getName(),
                    $constant[0]
                ));
            }
        }
    }
}
