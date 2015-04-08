<?php
interface EventTarget {
	public function addEventListener($aEventName, $aCallback, $aCapture);
	public function removeEventListener($aEventName, $aCallback, $aCapture);
	public function dispatchEvent(Event $aEvent);
}