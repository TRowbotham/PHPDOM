<?php

declare(strict_types=1);

namespace Rowbot\DOM\DynamicProperty;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Getter
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
