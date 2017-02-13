<?php
namespace phpjs\parser\tokens;

class AttributeToken implements Token
{
    public $name;
    public $namespace;
    public $prefix;
    public $value;

    public function __construct($aName = null, $aValue = null)
    {
        if ($aName !== null) {
            $this->name = $aName;
        }

        if ($aValue !== null) {
            $this->value = $aValue;
        }

        $this->namespace = null;
        $this->prefix = null;
    }

    public function getType()
    {
        return self::ATTRIBUTE_TOKEN;
    }
}
