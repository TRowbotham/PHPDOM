<?php
namespace Rowbot\DOM\Event;

interface EventListener
{
    public function handleEvent(Event $event);
}
