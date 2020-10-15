<?php

declare(strict_types=1);

namespace Rowbot\DOM\Event;

/**
 * @see https://dom.spec.whatwg.org/#dictdef-customeventinit
 */
class CustomEventInit extends EventInit
{
    /**
     * @var mixed
     */
    public $detail;

    public function __construct()
    {
        parent::__construct();

        $this->detail = null;
    }
}
