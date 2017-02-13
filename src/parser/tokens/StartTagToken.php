<?php
namespace phpjs\parser\tokens;

class StartTagToken extends TagToken
{
    private $selfClosingFlagAcknowledged;

    public function __construct($aTagName = null)
    {
        parent::__construct($aTagName);

        $this->selfClosingFlagAcknowledged = false;
    }

    public function acknowledge()
    {
        $this->selfClosingFlagAcknowledged = true;
    }

    public function wasAcknowledged()
    {
        return $this->selfClosingFlagAcknowledged;
    }

    public function getType()
    {
        return self::START_TAG_TOKEN;
    }
}
