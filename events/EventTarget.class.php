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
        $found = false;

        foreach ($this->mListeners as $l) {
            if ($l->isEqual($listener)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
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

        if ($flags & EventFlags::DISPATCH ||
            !($flags & EventFlags::INITIALIZED)
        ) {
            throw new InvalidStateError();
        }

        $aEvent->setIsTrusted(false);

        return $this->doDispatchEvent($aEvent, $this);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-dispatch
     *
     * @param Event $aEvent The event object for the event.
     *
     * @param EventTarget $aTarget The object that is the target of the event.
     *
     * @param EventTarget|null $aTargetOverride An alternate target for the
     *     event.
     *
     * @return bool Returns false if the event's default action was canceled,
     *     and true otherwise.
     */
    private function doDispatchEvent(
        $aEvent,
        $aTarget,
        $aTargetOverride = null
    ) {
        $aEvent->setFlag(EventFlags::DISPATCH);

        if ($aTargetOverride === null) {
            // Note: The targetOverride argument is only used by HTML and only
            // under very specific circumstances.
            $aTargetOverride = $aTarget;
        }

        $path = $aEvent->getPath();
        $path->push(
            ['item' => $aTarget, 'target' => $aTargetOverride]
        );
        $parent = $aTarget->getTheParent($aEvent);

        // While parent is non-null, if target's root is a shadow-including
        // inclusive ancestor of parent, then append (parent, null) to event's
        // path, otherwise, set target to parent and append (parent, target) to
        // event's path.
        while ($parent) {
            $root = $aTarget->getRootNode();

            if ($root->isShadowIncludingInclusiveAncestorOf($parent)) {
                $path->push(
                    ['item' => $parent, 'target' => null]
                );
            } else {
                $aTarget = $parent;
                $path->push(
                    ['item' => $parent, 'target' => $aTarget]
                );
            }

            $parent = $parent->getTheParent($aEvent);
        }

        $aEvent->setEventPhase(Event::CAPTURING_PHASE);

        // Set path's iterator mode so that it iterates in the reverse
        // direction.
        $path->setIteratorMode(
            \SplDoublyLinkedList::IT_MODE_LIFO |
            \SplDoublyLinkedList::IT_MODE_KEEP
        );

        foreach ($path as $index => $tuple) {
            $target = null;

            // Set event's target attribute to the target of the last tuple in
            // event's path, that is either tuple or preceding tuple, whose
            // target is non-null.
            for ($i = $path->count() - 1; $i >= 0; $i--) {
                if ($path[$i]['target'] !== null) {
                    $target = $path[$i]['target'];
                    break;
                }
            }

            $aEvent->setTarget($target);

            if ($tuple['target'] === null) {
                $this->invokeEventListener($tuple['item'], $aEvent);
            }
        }

        // Set path's iterator mode back to the default.
        $path->setIteratorMode(
            \SplDoublyLinkedList::IT_MODE_FIFO |
            \SplDoublyLinkedList::IT_MODE_KEEP
        );

        foreach ($path as $index => $tuple) {
            $target = null;

            // Set event's target attribute to the target of the last tuple in
            // event's path, that is either tuple or preceding tuple, whose
            // target is non-null.
            for ($i = $index; $i >= 0; $i--) {
                if ($path[$i]['target'] !== null) {
                    $target = $path[$i]['target'];
                    break;
                }
            }

            $aEvent->setTarget($target);

            if ($tuple['target'] !== null) {
                $aEvent->setEventPhase(Event::AT_TARGET);
            } else {
                $aEvent->setEventPhase(Event::BUBBLING_PHASE);
            }

            $phase = $aEvent->eventPhase;
            $shouldInvoke = ($phase == Event::BUBBLING_PHASE &&
                $aEvent->bubbles) || $phase == Event::AT_TARGET;

            if ($shouldInvoke) {
                $this->invokeEventListener($tuple['item'], $aEvent);
            }
        }

        $aEvent->unsetFlag(EventFlags::DISPATCH);
        $aEvent->setEventPhase(Event::NONE);
        $aEvent->setCurrentTarget(null);
        $aEvent->emptyPath();

        return !($aEvent->getFlags() & EventFlags::CANCELED);
    }

    /**
     * Prepares to execute callbacks for the specified event and object.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-listener-invoke
     *
     * @param EventTarget $aObject The target of the event being dispatched.
     *
     * @param Event $aEvent The event currently being dispatched.
     */
    private function invokeEventListener($aObject, $aEvent)
    {
        if ($aEvent->getFlags() & EventFlags::STOP_PROPAGATION) {
            return;
        }

        // This avoids event listeners added after this point from being run.
        // Note that removal still has an effect due to the removed field.
        $listeners = $aObject->mListeners;
        $aEvent->setCurrentTarget($aObject);
        $found = $this->innerInvokeEventListener($aObject, $aEvent, $listeners);

        if (!$found) {
            $originalEventType = $aEvent->type;

            // If event’s type attribute value is a match for any of the strings
            // in the first column in the following table, set event’s type
            // attribute value to the string in the second column on the same
            // row as the matching string, and terminate these substeps
            // otherwise.
            switch ($originalEventType) {
                case 'animationend':
                    $aEvent->setType('webkitAnimationEnd');

                    break;

                case 'animationiteration':
                    $aEvent->setType('webkitAnimationIteration');

                    break;

                case 'animationstart':
                    $aEvent->setType('webkitAnimationStart');

                    break;

                case 'transitionend':
                    $aEvent->setType('webkitTransitionEnd');

                    break;

                default:
                    return;
            }

            $this->innerInvokeEventListener($aObject, $aEvent, $listeners);
            $aEvent->setType($originalEventType);
        }
    }

    /**
     * Invokes all callbacks associated with a given event and object.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-listener-inner-invoke
     *
     * @param EventTarget $aObject The event target.
     *
     * @param Event $aEvent The event object.
     *
     * @param callable[] $aListeners A copy of the object's event listeners.
     *
     * @return bool
     */
    private function innerInvokeEventListener($aObject, $aEvent, $aListeners)
    {
        $found = false;

        foreach ($aListeners as $index => $listener) {
            if ($listener->getRemoved() == false) {
                // If event’s type attribute value is not listener’s type,
                // terminate these substeps (and run them for the next event
                // listener).
                if ($aEvent->type !== $listener->getType()) {
                    continue;
                }

                $found = true;
                $phase = $aEvent->eventPhase;

                // If event’s eventPhase attribute value is CAPTURING_PHASE and
                // listener’s capture is false, terminate these substeps (and
                // run them for the next event listener).
                if ($phase == Event::CAPTURING_PHASE &&
                    !$listener->getCapture()
                ) {
                    continue;
                }

                // If event’s eventPhase attribute value is BUBBLING_PHASE and
                // listener’s capture is true, terminate these substeps (and
                // run them for the next event listener).
                if ($phase == Event::BUBBLING_PHASE &&
                    $listener->getCapture()
                ) {
                    continue;
                }

                // If listener’s once is true, then remove listener from
                // object’s associated list of event listeners.
                if ($listener->getOnce()) {
                    array_splice($this->mListeners, $index, 1);
                }

                // If listener’s passive is true, set event’s in passive
                // listener flag.
                if ($listener->getPassive()) {
                    $aEvent->setFlag(EventFlags::IN_PASSIVE_LISTENER);
                }

                // Call listener’s callback’s handleEvent(), with event as
                // argument and event’s currentTarget attribute value as
                // callback this value. If this throws an exception, report the
                // exception.
                call_user_func($listener->getCallback(), $aEvent);

                // Unset event’s in passive listener flag.
                $aEvent->unsetFlag(EventFlags::IN_PASSIVE_LISTENER);

                // If event’s stop immediate propagation flag is set, return
                // found.
                if ($aEvent->getFlags() &
                    EventFlags::STOP_IMMEDIATE_PROPAGATION
                ) {
                    return $found;
                }
            }
        }

        return $found;
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
            if (isset($aOptions['capture'])) {
                $capture = (bool) $aOptions['capture'];
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
            if (isset($aOptions['passive'])) {
                $passive = (bool) $aOptions['passive'];
            }

            if (isset($aOptions['once'])) {
                $passive = (bool) $aOptions['once'];
            }
        }

        return [$capture, $passive, $once];
    }
}
