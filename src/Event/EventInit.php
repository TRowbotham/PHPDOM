<?php

declare(strict_types=1);

namespace Rowbot\DOM\Event;

/**
 * @see https://dom.spec.whatwg.org/#dictdef-eventinit
 */
class EventInit
{
    /**
     * @var bool
     */
    public $bubbles;

    /**
     * @var bool
     */
    public $cancelable;

    /**
     * @var bool
     */
    public $composed;

    public function __construct()
    {
        $this->bubbles = false;
        $this->cancelable = false;
        $this->composed = false;
    }
}
