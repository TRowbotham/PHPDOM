<?php

declare(strict_types=1);

namespace Rowbot\DOM\DynamicProperty;

use ReflectionMethod;
use Rowbot\DOM\Node;

class MethodGetter implements DynamicPropertyGetter
{
    private string $methodName;

    public function __construct(string $methodName)
    {
        $this->methodName = $methodName;
    }

    public function getValue(Node $object): mixed
    {
        $method = new ReflectionMethod($object, $this->methodName);

        return $method->invoke($object);
    }
}
