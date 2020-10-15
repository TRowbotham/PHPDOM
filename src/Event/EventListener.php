<?php

declare(strict_types=1);

namespace Rowbot\DOM\Event;

/**
 * @see https://dom.spec.whatwg.org/#callbackdef-eventlistener
 */
interface EventListener
{
    public function handleEvent(Event $event): void;
}
