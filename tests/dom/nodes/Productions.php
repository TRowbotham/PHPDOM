<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

trait Productions
{
    public static function invalidNamesProvider()
    {
        return [
            [""],
            ["invalid^Name"],
            ["\\"],
            ["'"],
            ['"'],
            ["0"],
            ["0:a"],
        ];
    }

    public static function validNamesProvider()
    {
        return [
            ["x"],
            [":"],
            ["a:0"],
        ];
    }

    public static function invalidQNamesProvider()
    {
        return [
            [":a"],
            ["b:"],
            ["x:y:z"],
        ];
    }
}
