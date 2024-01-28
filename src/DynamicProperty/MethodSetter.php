<?php

declare(strict_types=1);

namespace Rowbot\DOM\DynamicProperty;

use ReflectionMethod;
use Rowbot\DOM\Node;

class MethodSetter implements DynamicPropertySetter
{
    private string $methodName;

    public function __construct(string $methodName)
    {
        $this->methodName = $methodName;
    }

    public function setValue(Node $object, mixed $value): void
    {
        $method = new ReflectionMethod($object, $this->methodName);
        $method->invoke($object, $value);
    }
}
