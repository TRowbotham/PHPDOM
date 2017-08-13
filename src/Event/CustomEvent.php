<?php
namespace Rowbot\DOM\Event;

use Rowbot\DOM\Utils;

/**
 * Represents a custom event defined by the user which they can use to signal
 * that an event has occured in their code.
 *
 * @link https://dom.spec.whatwg.org/#customevent
 * @link https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent
 *
 * @property mixed $detail A proprerty that the user may use to attach
 *     additional useful information to the event.
 */
class CustomEvent extends Event
{
    private $mDetail;

    public function __construct(
        $aType,
        CustomEventInit $aEventInitDict = null
    ) {
        parent::__construct($aType);

        $initDict = $aEventInitDict ? $aEventInitDict : new CustomEventInit();
        $this->mBubbles = $initDict->bubbles;
        $this->mCancelable = $initDict->cancelable;
        $this->mDetail =& $initDict->detail;
    }

    public function __destruct()
    {
        $this->mDetail = null;
        parent::__destruct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'detail':
                return $this->mDetail;
            default:
                return parent::__get($aName);
        }
    }

    /**
     * Initializes or reinitializes a CustomEvent.
     *
     * @see https://dom.spec.whatwg.org/#dom-customevent-initcustomevent
     *
     * @param string $aType The type of event to be created.
     *
     * @param boolean $aBubbles Optional.  Whether or not the event will bubble
     *     up the tree, if the event is dispatched on an object that
     *     participates in a tree.  Defaults to false.
     *
     * @param boolean $aCancelable Optional.  Whether or not the event's default
     *     action can be prevented.  Defaults to false.
     *
     * @param mixed &$aDetail Optional.  Additional data to be sent along with
     *     the event.
     */
    public function initCustomEvent(
        $aType,
        $aBubbles = false,
        $aCancelable = false,
        &$aDetail = null
    ) {
        if ($this->mFlags & EventFlags::DISPATCH) {
            return;
        }

        $this->init(Utils::DOMString($aType), $aBubbles, $aCancelable);
        $this->mDetail =& $aDetail;
    }
}
