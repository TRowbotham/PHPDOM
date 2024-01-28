<?php

declare(strict_types=1);

namespace Rowbot\DOM\DynamicProperty;

use ReflectionProperty;
use Rowbot\DOM\Node;

class PropertySetter implements DynamicPropertySetter
{
    private string $propertyName;

    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

    public function setValue(Node $object, mixed $value): void
    {
        $property = new ReflectionProperty($object, $this->propertyName);

        $property->setValue($object, $value);
    }
}
