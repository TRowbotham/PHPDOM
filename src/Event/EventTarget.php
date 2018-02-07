<?php
namespace Rowbot\DOM\Event;

use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Utils;

/**
 * @see https://dom.spec.whatwg.org/#eventtarget
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget
 */
abstract class EventTarget
{
    private $listeners;

    protected function __construct()
    {
        $this->listeners = [];
    }

    /**
     * Registers a callback for a specified event on the current node.
     *
     * @param string $type The name of the event to listen for.
     *
     * @param callable|EventListener $callback A callback that will be executed
     *     when the event occurs.  If an object that inherits from the
     *     EventListener interface is given, it will use the handleEvent method
     *     on the object as the callback.
     *
     * @param bool|bool[] $options Optional. If a boolean is give, it specifies
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
    public function addEventListener($type, $callback, $options = false) {
        // If callback is null, terminate these steps.
        if ($callback === null) {
            return;
        }

        list($capture, $passive, $once) = $this->flattenMoreOptions($options);

        if (\is_object($callback) && $callback instanceof EventListener) {
            $callback = [$callback, 'handleEvent'];
        } elseif (\is_callable($callback)) {
            $callback = $callback;
        } else {
            return;
        }

        $listener = new Listener(
            Utils::DOMString($type),
            $callback,
            $capture,
            $once,
            $passive
        );
        $found = false;

        foreach ($this->listeners as $l) {
            if ($l->isEqual($listener)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->listeners[] = $listener;
        }
    }

    /**
     * Unregisters a callback for a specified event on the current node.
     *
     * @param string $type The name of the event to listen for.
     *
     * @param callable|EventListener $callback A callback that will be executed
     *     when the event occurs.  If an object that inherits from the
     *     EventListener interface is given, it will use the handleEvent method
     *     on the object as the callback.
     *
     * @param bool|bool[] $options Optional. If a boolean is give, it specifies
     *     whether or not the event should be handled during the capturing or
     *     bubbling phase. If an array is given, the following keys and values
     *     are accepted:
     *
     *          [
     *              capture => boolean
     *          ]
     */
    public function removeEventListener($type, $callback, $options = false) {
        // If callback is null, terminate these steps.
        if ($callback === null) {
            return;
        }

        $capture = $this->flattenOptions($options);

        if (\is_object($callback) && $callback instanceof EventListener) {
            $callback = [$callback, 'handleEvent'];
        } elseif (\is_callable($callback)) {
            $callback = $callback;
        } else {
            return;
        }

        $listener = new Listener(
            Utils::DOMString($type),
            $callback,
            $capture
        );

        foreach ($this->listeners as $index => $eventListener) {
            if ($eventListener->isEqual($listener)) {
                $eventListener->setRemoved(true);
                \array_splice($this->listeners, $index, 1);
                break;
            }
        }
    }

    /**
     * Dispatches an event at the current EventTarget, which will then invoke
     * any event listeners on the node and its ancestors.
     *
     * @param Event $event An object representing the specific event dispatched
     *     with information regarding that event.
     *
     * @return boolean Returns true if the event is not cancelable or if the
     *     preventDefault() method is not invoked, otherwise it returns false.
     */
    public function dispatchEvent(Event $event)
    {
        $flags = $event->getFlags();

        if ($flags & EventFlags::DISPATCH
            || !($flags & EventFlags::INITIALIZED)
        ) {
            throw new InvalidStateError();
        }

        $event->setIsTrusted(false);

        return $this->doDispatchEvent($event, $this);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-dispatch
     *
     * @param Event $event The event object for the event.
     *
     * @param EventTarget $target The object that is the target of the event.
     *
     * @param bool $legacyTargetOverride A flag indicating that the
     *     event's target should be overridden. Only useful for HTML.
     *
     * @return bool Returns false if the event's default action was canceled,
     *     and true otherwise.
     */
    private function doDispatchEvent(
        $event,
        $target,
        $legacyTargetOverride = false
    ) {
        $event->setFlag(EventFlags::DISPATCH);

        // Let targetOverride be target, if legacy target override flag is not
        // given, and target's associated Document otherwise.
        $targetOverride = $target;

        if ($legacyTargetOverride === null) {
            // Note: legacy target override flag is only used by HTML and only
            // when target is a Window object.
            $targetOverride = $target->getNodeDocument();
        }

        // Let relatedTarget be the result of retargeting event’s relatedTarget
        // against target if event’s relatedTarget is non-null, and null
        // otherwise.
        $relTarget = $event->getRelatedTarget();
        $relatedTarget = null;

        if ($relTarget) {
            $relatedTarget = Utils::retargetObject($relTarget, $target);
        }

        // If target is relatedTarget and target is not event’s relatedTarget,
        // then return true.
        if ($target === $relatedTarget
            && $target !== $event->getRelatedTarget()
        ) {
            return true;
        }

        // Append (target, targetOverride, relatedTarget) to event’s path.
        $path = $event->getPath();
        $path->push([
            'item'          => $target,
            'target'        => $targetOverride,
            'relatedTarget' => $relatedTarget
        ]);

        // Let isActivationEvent be true, if event is a MouseEvent object and
        // event’s type attribute is "click", and false otherwise.
        $isActivationEvent = false;

        if ($event instanceof MouseEvent && $event->type === 'click') {
            $isActivationEvent = true;
        }

        // Let activationTarget be target, if isActivationEvent is true and
        // target has activation behavior, and null otherwise.
        $activationTarget = null;

        if ($isActivationEvent && $target->hasActivationBehavior()) {
            $activationTarget = $target;
        }

        // Let parent be the result of invoking target’s get the parent with
        // event.
        $parent = $target->getTheParent($event);

        while ($parent) {
            $relatedTarget = null;

            // Let relatedTarget be the result of retargeting event’s
            // relatedTarget against parent if event’s relatedTarget is
            // non-null, and null otherwise.
            if ($event->getRelatedTarget() !== null) {
                $relatedTarget = Utils::retargetObject(
                    $event->getRelatedTarget(),
                    $parent
                );
            }

            $root = $target->getRootNode();

            if ($root->isShadowIncludingInclusiveAncestorOf($parent)) {
                // If isActivationEvent is true, event’s bubbles attribute is
                // true, activationTarget is null, and parent has activation
                // behavior, then set activationTarget to parent.
                if ($isActivationEvent
                    && $event->bubbles
                    && $activationTarget === null
                    && $parent->hasActivationBehavior()
                ) {
                    $activationTarget = $parent;
                }

                // Append (parent, null, relatedTarget) to event’s path.
                $path->push([
                    'item'          => $parent,
                    'target'        => null,
                    'relatedTarget' => $relatedTarget
                ]);
            } elseif ($parent === $relatedTarget) {
                $parent = null;
            } else {
                $target = $parent;

                // If isActivationEvent is true, activationTarget is null, and
                // target has activation behavior, then set activationTarget to
                // target.
                if ($isActivationEvent
                    && $activationTarget === null
                    && $target->hasActivationBehavior()
                ) {
                    $activationTarget = $target;
                }

                // Append (parent, target, relatedTarget) to event’s path.
                $path->push([
                    'item'          => $parent,
                    'target'        => $target,
                    'relatedTarget' => $relatedTarget
                ]);
            }

            // If parent is non-null, then set parent to the result of invoking
            // parent’s get the parent with event.
            if ($parent !== null) {
                $parent = $parent->getTheParent($event);
            }
        }

        $event->setEventPhase(Event::CAPTURING_PHASE);

        // If activationTarget is non-null and activationTarget has
        // legacy-pre-activation behavior, then run activationTarget’s
        // legacy-pre-activation behavior.
        if ($activationTarget !== null
            && $activationTarget->hasLegacyPreActivationBehavior()
        ) {
            $activationTarget->legacyPreActivationBehavior();
        }

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

            $event->setTarget($target);

            // Set event’s relatedTarget to tuple’s relatedTarget.
            $event->setRelatedTarget($tuple['relatedTarget']);

            // Run the retargeting steps with event.
            $event->retarget();

            // If tuple’s target is null, then invoke tuple’s item with event.
            if ($tuple['target'] === null) {
                $this->invokeEventListener($tuple['item'], $event);
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

            $event->setTarget($target);

            // Set event’s relatedTarget to tuple’s relatedTarget.
            $event->setRelatedTarget($tuple['relatedTarget']);

            // Run the retargeting steps with event.
            $event->retarget();

            // If tuple’s target is non-null, then set event’s eventPhase
            // attribute to AT_TARGET. Otherwise, set event’s eventPhase
            // attribute to BUBBLING_PHASE.
            if ($tuple['target'] !== null) {
                $event->setEventPhase(Event::AT_TARGET);
            } else {
                $event->setEventPhase(Event::BUBBLING_PHASE);
            }

            // If either event’s eventPhase attribute is BUBBLING_PHASE and
            // event’s bubbles attribute is true or event’s eventPhase attribute
            // is AT_TARGET, then invoke tuple’s item with event.
            $phase = $event->eventPhase;
            $shouldInvoke = ($phase == Event::BUBBLING_PHASE
                && $event->bubbles) || $phase == Event::AT_TARGET;

            if ($shouldInvoke) {
                $this->invokeEventListener($tuple['item'], $event);
            }
        }

        // Unset event’s dispatch flag, stop propagation flag, and stop
        // immediate propagation flag.
        $event->unsetFlag(
            EventFlags::DISPATCH
            | EventFlags::STOP_PROPAGATION
            | EventFlags::STOP_IMMEDIATE_PROPAGATION
        );

        // Set event’s eventPhase attribute to NONE.
        $event->setEventPhase(Event::NONE);

        // Set event’s currentTarget attribute to null.
        $event->setCurrentTarget(null);

        // Set event’s path to the empty list.
        $event->emptyPath();

        if ($activationTarget !== null) {
            // If event’s canceled flag is unset, then run activationTarget’s
            // activation behavior with event.
            if (!($event->getFlags() & EventFlags::CANCELED)) {
                $activationTarget->runActivationBehavior($event);

                // Otherwise, if activationTarget has legacy-canceled-activation
                // behavior, then run activationTarget’s
                // legacy-canceled-activation behavior.
            } elseif ($activationTarget->hasLegacyCanceledActivationBehavior()) {
                $activationTarget->runLegacyCanceledActivationBehavior();
            }
        }

        // Return false if event’s canceled flag is set, and true otherwise.
        return !($event->getFlags() & EventFlags::CANCELED);
    }

    /**
     * Prepares to execute callbacks for the specified event and object.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-listener-invoke
     *
     * @param EventTarget $object The target of the event being dispatched.
     *
     * @param Event $event The event currently being dispatched.
     */
    private function invokeEventListener($object, $event)
    {
        if ($event->getFlags() & EventFlags::STOP_PROPAGATION) {
            return;
        }

        // This avoids event listeners added after this point from being run.
        // Note that removal still has an effect due to the removed field.
        $listeners = $object->listeners;
        $event->setCurrentTarget($object);
        $found = $this->innerInvokeEventListener($object, $event, $listeners);

        if (!$found && $event->isTrusted) {
            $originalEventType = $event->type;

            // If event’s type attribute value is a match for any of the strings
            // in the first column in the following table, set event’s type
            // attribute value to the string in the second column on the same
            // row as the matching string, and terminate these substeps
            // otherwise.
            switch ($originalEventType) {
                case 'animationend':
                    $event->setType('webkitAnimationEnd');

                    break;

                case 'animationiteration':
                    $event->setType('webkitAnimationIteration');

                    break;

                case 'animationstart':
                    $event->setType('webkitAnimationStart');

                    break;

                case 'transitionend':
                    $event->setType('webkitTransitionEnd');

                    break;

                default:
                    return;
            }

            $this->innerInvokeEventListener($object, $event, $listeners);
            $event->setType($originalEventType);
        }
    }

    /**
     * Invokes all callbacks associated with a given event and object.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-listener-inner-invoke
     *
     * @param EventTarget $object The event target.
     *
     * @param Event $event The event object.
     *
     * @param callable[] $listeners A copy of the object's event listeners.
     *
     * @return bool
     */
    private function innerInvokeEventListener($object, $event, $listeners)
    {
        $found = false;
        $indexOffset = 0;

        foreach ($listeners as $index => $listener) {
            if ($listener->getRemoved() == false) {
                // If event’s type attribute value is not listener’s type,
                // terminate these substeps (and run them for the next event
                // listener).
                if ($event->type !== $listener->getType()) {
                    continue;
                }

                $found = true;
                $phase = $event->eventPhase;

                // If event’s eventPhase attribute value is CAPTURING_PHASE and
                // listener’s capture is false, terminate these substeps (and
                // run them for the next event listener).
                if ($phase == Event::CAPTURING_PHASE
                    && !$listener->getCapture()
                ) {
                    continue;
                }

                // If event’s eventPhase attribute value is BUBBLING_PHASE and
                // listener’s capture is true, terminate these substeps (and
                // run them for the next event listener).
                if ($phase == Event::BUBBLING_PHASE
                    && $listener->getCapture()
                ) {
                    continue;
                }

                // If listener’s once is true, then remove listener from
                // object’s associated list of event listeners.
                if ($listener->getOnce()) {
                    \array_splice(
                        $object->listeners,
                        $index - $indexOffset,
                        1
                    );
                    ++$indexOffset;
                }

                // If listener’s passive is true, set event’s in passive
                // listener flag.
                if ($listener->getPassive()) {
                    $event->setFlag(EventFlags::IN_PASSIVE_LISTENER);
                }

                // Call listener’s callback’s handleEvent(), with event as
                // argument and event’s currentTarget attribute value as
                // callback this value. If this throws an exception, report the
                // exception.
                \call_user_func($listener->getCallback(), $event);

                // Unset event’s in passive listener flag.
                $event->unsetFlag(EventFlags::IN_PASSIVE_LISTENER);

                // If event’s stop immediate propagation flag is set, return
                // found.
                if ($event->getFlags()
                    & EventFlags::STOP_IMMEDIATE_PROPAGATION
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
     * @param Event $event The Event object
     *
     * @return EventTarget|null
     */
    protected function getTheParent($event)
    {
        return null;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-flatten-options
     *
     * @param bool|bool[] $options A boolean or array of booleans.
     *
     * @return bool
     */
    private function flattenOptions($options)
    {
        $capture = false;

        if (\is_bool($options)) {
            $capture = $options;
        } elseif (\is_array($options)) {
            if (isset($options['capture'])) {
                $capture = (bool) $options['capture'];
            }
        }

        return $capture;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#event-flatten-more
     *
     * @param bool|bool[] $options A boolean or array of booleans.
     *
     * @return bool[]
     */
    private function flattenMoreOptions($options)
    {
        $capture = $this->flattenOptions($options);
        $once = false;
        $passive = false;

        if (\is_array($options)) {
            if (isset($options['passive'])) {
                $passive = (bool) $options['passive'];
            }

            if (isset($options['once'])) {
                $passive = (bool) $options['once'];
            }
        }

        return [$capture, $passive, $once];
    }

    protected function hasActivationBehavior()
    {
        return false;
    }

    protected function hasLegacyPreActivationBehavior()
    {
        return false;
    }

    protected function hasLegacyCanceledActivationBehavior()
    {
        return false;
    }
}
