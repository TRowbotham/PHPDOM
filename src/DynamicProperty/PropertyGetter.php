<?php

declare(strict_types=1);

namespace Rowbot\DOM\DynamicProperty;

use ReflectionProperty;
use Rowbot\DOM\Node;

class PropertyGetter implements DynamicPropertyGetter
{
    private string $propertyName;

    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

    public function getValue(Node $object): mixed
    {
        $property = new ReflectionProperty($object, $this->propertyName);

        return $property->getValue($object);
    }
}
