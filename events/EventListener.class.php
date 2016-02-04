<?php
namespace phpjs\events;

interface EventListener {
	public function handleEvent(Event $aEvent);
}
