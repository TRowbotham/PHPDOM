<?php
namespace phpjs\events;

class CustomEventInit extends EventInit {
    public $detail;

    public function __construct() {
        parent::__construct();

        $this->detail = null;
    }

    public function __destruct()
    {
        $this->detail = null;
    }
}
