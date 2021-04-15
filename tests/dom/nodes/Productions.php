<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

trait Productions
{
    public function invalidNamesProvider()
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

    public function validNamesProvider()
    {
        return [
            ["x"],
            [":"],
            ["a:0"],
        ];
    }

    public function invalidQNamesProvider()
    {
        return [
            [":a"],
            ["b:"],
            ["x:y:z"],
        ];
    }
}
