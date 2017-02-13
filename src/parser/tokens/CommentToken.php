<?php
namespace phpjs\parser\tokens;

class CommentToken implements Token
{
    public $data;

    public function __construct($aData = null)
    {
        $this->data = null;

        if ($aData !== null) {
            $this->data = $aData;
        }
    }

    public function getType()
    {
        return self::COMMENT_TOKEN;
    }
}
