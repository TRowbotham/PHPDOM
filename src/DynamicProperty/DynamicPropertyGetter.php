<?php

declare(strict_types=1);

namespace Rowbot\DOM\DynamicProperty;

use Rowbot\DOM\Node;

interface DynamicPropertyGetter
{
    public function getValue(Node $object): mixed;
}
