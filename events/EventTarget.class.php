<?php
namespace phpjs\events;

use phpjs\exceptions\InvalidStateError;
use phpjs\Utils;

/**
 * @see https://dom.spec.whatwg.org/#eventtarget
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget
 */
abstract class EventTarget
{
    private $mEvents;

    protected function __construct()
    {
        $this->mEvents = [];
    }

	/**
     * Registers a callback for a specified event on the current node.
     *
     * @param string $aEventName The name of the event to listen for.
     *
     * @param callable|EventListener $aCallback A callback that will be executed
     *     when the event occurs.  If an object that inherits from the
     *     EventListener interface is given, it will use the handleEvent method
     *     on the object as the callback.
     *
     * @param boolean $aUseCapture Optional. Specifies whether or not the event
     *     should be handled during the capturing or bubbling phase.
     */
    public function addEventListener(
        $aEventName,
        $aCallback,
        $aUseCapture = false
    ) {
        if (!$aCallback) {
            return;
        }

        if (is_object($aCallback) && $aCallback instanceof EventListener) {
            $callback = array($aCallback, 'handleEvent');
        } else {
            $callback = $aCallback;
        }

        $listener = array(
            'type' => Utils::DOMString($aEventName),
            'callback' => $aCallback,
            'capture' => $aUseCapture
        );

        if (!in_array($listener, $this->mEvents)) {
            array_unshift($this->mEvents, $listener);
        }
    }

	/**
     * Unregisters a callback for a specified event on the current node.
     *
     * @param string $aEventName The name of the event to listen for.
     *
     * @param callable|EventListener $aCallback A callback that will be executed
     *     when the event occurs.  If an object that inherits from the
     *     EventListener interface is given, it will use the handleEvent method
     *     on the object as the callback.
     *
     * @param boolean $aUseCapture Optional. Specifies whether or not the event
     *     should be handled during the capturing or bubbling phase.
     */
    public function removeEventListener(
        $aEventName,
        $aCallback,
        $aUseCapture = false
    ) {
        if (is_object($aCallback) && $aCallback instanceof EventListener) {
            $callback = array($aCallback, 'handleEvent');
        } else {
            $callback = $aCallback;
        }

        $listener = array(
            'type' => Utils::DOMString($aEventName),
            'callback' => $callback,
            'capture' => $aUseCapture
        );
        $index = array_search($listener, $this->mEvents);

        if ($index !== false) {
            array_splice($this->mEvents, $index, 1);
        }
    }

    /**
     * Dispatches an event at the current EventTarget, which will then invoke
     * any event listeners on the node and its ancestors.
     *
     * @param Event $aEvent An object representing the specific event dispatched
     *     with information regarding that event.
     *
     * @return boolean Returns true if the event is not cancelable or if the
     *     preventDefault() method is not invoked, otherwise it returns false.
     */
    public function dispatchEvent(Event $aEvent)
    {
        $flags = $aEvent->_getFlags();
        $eventState = $flags & Event::EVENT_DISPATCHED ||
            $flags & Event::EVENT_INITIALIZED;

        if ($eventState) {
            throw new InvalidStateError();
        }

        $aEvent->_setIsTrusted(false);
        $aEvent->_setFlag(Event::EVENT_DISPATCHED);
        $aEvent->_setTarget($this);
        $eventPath = array();
        $node = $this->mParentNode;

        while ($node) {
            $eventPath[] = $node;
            $node = $node->mParentNode;
        }

        $aEvent->_setEventPhase(Event::CAPTURING_PHASE);

        foreach ($eventPath as $eventTarget) {
            if ($aEvent->_getFlags() & Event::EVENT_STOP_PROPAGATION) {
                break;
            }

            $this->invokeEventListener($aEvent, $eventTarget);
        }

        $aEvent->_setEventPhase(Event::AT_TARGET);

        if (!($aEvent->_getFlags() & Event::EVENT_STOP_PROPAGATION)) {
            $this->invokeEventListener($aEvent, $aEvent->target);
        }

        if ($aEvent->bubbles) {
            $aEvent->_setEventPhase(Event::BUBBLING_PHASE);

            foreach (array_reverse($eventPath) as $eventTarget) {
                if ($aEvent->_getFlags() & Event::EVENT_STOP_PROPAGATION) {
                    break;
                }

                $this->invokeEventListener($aEvent, $eventTarget);
            }
        }

        $aEvent->_unsetFlag(Event::EVENT_DISPATCHED);
        $aEvent->_setEventPhase(Event::NONE);
        $aEvent->_setCurrentTarget(null);

        return !$aEvent->cancelable ||
            !($aEvent->_getFlags() & Event::EVENT_CANCELED);
    }

    /**
     * Always returns null except for Nodes, Shadow Roots, and Documents, which
     * override this algorithm.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#get-the-parent
     *
     * @param Event $aEvent The Event object
     *
     * @return EventTarget|null
     */
    protected function getTheParent($aEvent) {
        return null;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-flatten-options
     *
     * @param bool|bool[] $aOptions A boolean or array of booleans.
     *
     * @return bool
     */
    private function flattenOptions($aOptions)
    {
        $capture = false;

        if (is_bool($aOptions)) {
            $capture = $aOptions;
        } elseif (is_array($aOptions)) {
            if (isset($aOptions['capture']) && is_bool($aOptions['capture'])) {
                $capture = $aOptions['capture'];
            }
        }

        return $capture;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#event-flatten-more
     *
     * @param bool|bool[] $aOptions A boolean or array of booleans.
     *
     * @return bool[]
     */
    private function flattenMoreOptions($aOptions)
    {
        $capture = $this->flattenOptions($aOptions);
        $once = false;
        $passive = false;

        if (is_array($aOptions)) {
            if (isset($aOptions['passive']) && is_bool($aOptions['passive'])) {
                $passive = $aOptions['passive'];
            }

            if (isset($aOptions['once']) && is_bool($aOptions['once'])) {
                $passive = $aOptions['once'];
            }
        }

        return [$capture, $passive, $once];
    }

    /**
     * Invokes all callbacks associated with a given event and Node.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-event-listener-invoke
     *
     * @param Event $aEvent The event currently being dispatched.
     *
     * @param Node $aTarget The current target of the event being dispatched.
     */
    private function invokeEventListener($aEvent, $aTarget)
    {
        $listeners = $aTarget->mEvents;
        $aEvent->_setCurrentTarget($aTarget);

        for ($i = 0, $count = count($listeners); $i < $count; $i++) {
            if (
                $aEvent->_getFlags() & Event::EVENT_STOP_IMMEDIATE_PROPAGATION
            ) {
                break;
            }

            $phase = $aEvent->eventPhase;

            if (
                $aEvent->type !== $listeners[$i]['type'] ||
                ($phase === Event::CAPTURING_PHASE &&
                    !$listeners[$i]['capture']) ||
                ($phase === Event::BUBBLING_PHASE &&
                    $listeners[$i]['capture'])
            ) {
                continue;
            }

            call_user_func($listeners[$i]['callback'], $aEvent);
        }
    }
}
