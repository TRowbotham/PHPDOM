<?php

namespace Rowbot\DOM\Tests\dom;

use Generator;
use ReflectionClass;
use Rowbot\DOM\Element\ElementFactory;

trait HTMLElementInterfaces
{
    public function getHTMLElementInterfaces(): Generator
    {
        $reflection = new ReflectionClass(ElementFactory::class);

        $map = $reflection->getConstant('HTML_ELEMENTS');

        foreach ($map as $name => $className) {
            yield [$name, "Rowbot\\DOM\\Element\\HTML\\{$className}"];
        }
    }
}
