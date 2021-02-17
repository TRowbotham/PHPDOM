<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * The spec does not specifically define an attribute token, however, we use it here to store the data for attributes
 * that are then added to the attributes list of start and end tokens.
 */
class AttributeToken implements Token
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string|null
     */
    public $namespace;

    /**
     * @var string|null
     */
    public $prefix;

    /**
     * @var string
     */
    public $value;

    public function __construct(string $name = '', string $value = '')
    {
        $this->name = $name;
        $this->value = $value;
        $this->namespace = null;
        $this->prefix = null;
    }
}
