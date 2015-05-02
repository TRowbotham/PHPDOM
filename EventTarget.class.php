<?php
// https://developer.mozilla.org/en-US/docs/Web/API/EventTarget
// https://dom.spec.whatwg.org/#eventtarget

interface EventTarget {
	public function addEventListener($aEventName, $aCallback, $aCapture);
	public function removeEventListener($aEventName, $aCallback, $aCapture);
	public function dispatchEvent(Event $aEvent);
}