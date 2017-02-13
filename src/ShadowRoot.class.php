<?php
namespace phpjs;

use phpjs\elements\Element;

/**
 * @see https://dom.spec.whatwg.org/#interface-shadowroot
 * @see https://w3c.github.io/webcomponents/spec/shadow/#the-shadowroot-interface
 */
class ShadowRoot extends DocumentFragment
{
    protected $mode;

    public function __construct()
    {
        parent::__construct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'host':
                return $this->mHost;

            case 'mode':
                return $this->mode;

            default:
                return parent::__get($aName);
        }
    }

    /**
     * Sets the Shadow Root's host.  A Shadow Root's host can never be null.
     *
     * @internal
     *
     * @param Element $aHost An Element.
     */
    public function setHost(Element $aHost = null)
    {
        $this->mHost = $aHost;
    }

    /**
     * Sets the Shadow Root's mode to one of the modes in ShadowRootMode.
     *
     * @internal
     *
     * @param string $aMode A mode representing the open or closed state.
     */
    public function setMode($aMode)
    {
        $this->mode = $aMode;
    }

    /**
     * Returns null if event’s composed flag is unset and shadow root is the
     * root of event’s path’s first tuple’s item, and shadow root’s host
     * otherwise.
     *
     * @see EventTarget::getTheParent
     *
     * @param Event $aEvent An Event object.
     *
     * @return Element|null
     */
    protected function getTheParent($aEvent)
    {
        if (!$aEvent->composed &&
            $aEvent->getPath()[0]['item']->getRootNode() === $this) {
            return null;
        }

        return $this->mHost;
    }
}
