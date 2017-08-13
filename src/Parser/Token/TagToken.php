<?php
namespace Rowbot\DOM\Parser\Token;

use SplDoublyLinkedList;

abstract class TagToken implements Token
{
    public $attributes;
    public $isSelfClosing;
    public $tagName;

    public function __construct($aTagName = null)
    {
        $this->attributes = new SplDoublyLinkedList();
        $this->isSelfClosing = null;
        $this->tagName = null;

        if ($aTagName !== null) {
            $this->tagName = $aTagName;
        }
    }

    public function clearAttributes()
    {
        $this->attributes = new SplDoublyLinkedList();
    }

    public function isSelfClosing()
    {
        return $this->isSelfClosing;
    }

    public function setSelfClosingFlag()
    {
        $this->isSelfClosing = true;
    }
}
