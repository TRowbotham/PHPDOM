<?php
declare(strict_types=1);

namespace Rowbot\DOM\Event;

interface EventListener
{
    public function handleEvent(Event $event);
}
