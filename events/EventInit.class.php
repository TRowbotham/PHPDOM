<?php
namespace phpjs\events;

class EventInit
{
    public $bubbles;
    public $cancelable;

    public function __construct()
    {
        $this->bubbles = false;
        $this->cancelable = false;
    }
}
