<?php
interface EventListener {
	public function handleEvent(Event $aEvent);
}