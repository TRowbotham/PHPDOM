<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * The spec does not specifically define an attribute token, however, we use it here to store the data for attributes
 * that are then added to the attributes list of start and end tokens.
 *
 * {@inheirtDoc}
 */
class AttributeToken implements Token
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var null
     */
    public $namespace;

    /**
     * @var null
     */
    public $prefix;

    /**
     * @var string
     */
    public $value;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function __construct(string $name = null, string $value = null)
    {
        if ($name !== null) {
            $this->name = $name;
        }

        if ($value !== null) {
            $this->value = $value;
        }

        $this->namespace = null;
        $this->prefix = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): int
    {
        return self::ATTRIBUTE_TOKEN;
    }
}
