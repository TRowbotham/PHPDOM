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
    private $mListeners;

    protected function __construct()
    {
        $this->mListeners = [];
    }

	/**
     * Registers a callback for a specified event on the current node.
     *
     * @param string $aType The name of the event to listen for.
     *
     * @param callable|EventListener $aCallback A callback that will be executed
     *     when the event occurs.  If an object that inherits from the
     *     EventListener interface is given, it will use the handleEvent method
     *     on the object as the callback.
     *
     * @param bool|bool[] $aOptions Optional. If a boolean is give, it specifies
     *     whether or not the event should be handled during the capturing or
     *     bubbling phase. If an array is given, the following keys and values
     *     are accepted:
     *
     *         [
     *             capture => boolean
     *             passive => boolean
     *             once    => boolean
     *         ]
     *
     */
    public function addEventListener(
        $aType,
        $aCallback,
        $aOptions = false
    ) {
        // If callback is null, terminate these steps.
        if ($aCallback === null) {
            return;
        }

        list($capture, $passive, $once) = $this->flattenMoreOptions($aOptions);

        if (is_object($aCallback) && $aCallback instanceof EventListener) {
            $callback = [$aCallback, 'handleEvent'];
        } elseif (is_callable($aCallback)) {
            $callback = $aCallback;
        } else {
            return;
        }

        $listener = new Listener(
            Utils::DOMString($aType),
            $callback,
            $capture,
            $once,
            $passive
        );

        if (!in_array($listener, $this->mListeners)) {
            $this->mListeners[] = $listener;
        }
    }

	/**
     * Unregisters a callback for a specified event on the current node.
     *
     * @param string $aType The name of the event to listen for.
     *
     * @param callable|EventListener $aCallback A callback that will be executed
     *     when the event occurs.  If an object that inherits from the
     *     EventListener interface is given, it will use the handleEvent method
     *     on the object as the callback.
     *
     * @param bool|bool[] $aOptions Optional. If a boolean is give, it specifies
     *     whether or not the event should be handled during the capturing or
     *     bubbling phase. If an array is given, the following keys and values
     *     are accepted:
     *
     *          [
     *              capture => boolean
     *          ]
     */
    public function removeEventListener(
        $aType,
        $aCallback,
        $aOptions = false
    ) {
        // If callback is null, terminate these steps.
        if ($aCallback === null) {
            return;
        }

        $capture = $this->flattenOptions($aOptions);

        if (is_object($aCallback) && $aCallback instanceof EventListener) {
            $callback = [$aCallback, 'handleEvent'];
        } elseif (is_callable($aCallback)) {
            $callback = $aCallback;
        } else {
            return;
        }

        $listener = new Listener(
            Utils::DOMString($aType),
            $callback,
            $capture
        );

        foreach ($this->mListeners as $index => $eventListener) {
            if ($eventListener->isEqual($listener)) {
                $eventListener->setRemoved(true);
                array_splice($this->mListeners, $index, 1);
                break;
            }
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
        $flags = $aEvent->getFlags();
        $eventState = $flags & EventFlags::DISPATCH ||
            $flags & EventFlags::INITIALIZED;

        if ($eventState) {
            throw new InvalidStateError();
        }

        $aEvent->setIsTrusted(false);
        $aEvent->setFlag(EventFlags::DISPATCH);
        $aEvent->setTarget($this);
        $eventPath = array();
        $node = $this->mParentNode;

        while ($node) {
            $eventPath[] = $node;
            $node = $node->mParentNode;
        }

        $aEvent->setEventPhase(Event::CAPTURING_PHASE);

        foreach ($eventPath as $eventTarget) {
            if ($aEvent->getFlags() & EventFlags::STOP_PROPAGATION) {
                break;
            }

            $this->invokeEventListener($aEvent, $eventTarget);
        }

        $aEvent->setEventPhase(Event::AT_TARGET);

        if (!($aEvent->getFlags() & EventFlags::STOP_PROPAGATION)) {
            $this->invokeEventListener($aEvent, $aEvent->target);
        }

        if ($aEvent->bubbles) {
            $aEvent->setEventPhase(Event::BUBBLING_PHASE);

            foreach (array_reverse($eventPath) as $eventTarget) {
                if ($aEvent->getFlags() & EventFlags::STOP_PROPAGATION) {
                    break;
                }

                $this->invokeEventListener($aEvent, $eventTarget);
            }
        }

        $aEvent->unsetFlag(EventFlags::DISPATCH);
        $aEvent->setEventPhase(Event::NONE);
        $aEvent->setCurrentTarget(null);

        return !$aEvent->cancelable ||
            !($aEvent->getFlags() & EventFlags::CANCELED);
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
        $aEvent->setCurrentTarget($aTarget);

        for ($i = 0, $count = count($listeners); $i < $count; $i++) {
            if (
                $aEvent->getFlags() & EventFlags::STOP_IMMEDIATE_PROPAGATION
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
