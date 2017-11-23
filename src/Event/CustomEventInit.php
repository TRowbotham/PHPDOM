<?php
namespace Rowbot\DOM\Event;

class CustomEventInit extends EventInit
{
    public $detail;

    public function __construct()
    {
        parent::__construct();

        $this->detail = null;
    }
}
