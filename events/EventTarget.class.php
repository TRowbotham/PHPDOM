<?php
namespace phpjs\events;

/**
 * @see https://dom.spec.whatwg.org/#eventtarget
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget
 */
interface EventTarget
{
	public function addEventListener($aEventName, $aCallback, $aCapture);
	public function removeEventListener($aEventName, $aCallback, $aCapture);
	public function dispatchEvent(Event $aEvent);
}
