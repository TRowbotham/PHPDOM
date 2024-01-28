<?php

declare(strict_types=1);

namespace Rowbot\DOM\DynamicProperty;

use Rowbot\DOM\Node;

interface DynamicPropertySetter
{
    public function setValue(Node $object, mixed $value): void;
}
