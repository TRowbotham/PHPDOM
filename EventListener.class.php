<?php
namespace phpjs;

interface EventListener {
	public function handleEvent(Event $aEvent);
}
