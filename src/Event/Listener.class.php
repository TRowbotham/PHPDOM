<?php
namespace Rowbot\DOM\Event;

class Listener
{
    private $mType;
    private $mCallback;
    private $mCapture;
    private $mPassive;
    private $mOnce;
    private $mRemoved;

    public function __construct(
        $aType,
        $aCallback,
        $aCapture,
        $aOnce = false,
        $aPassive = false
    ) {
        $this->mType = $aType;
        $this->mCallback = $aCallback;
        $this->mCapture = $aCapture;
        $this->mOnce = $aOnce;
        $this->mPassive = $aPassive;
        $this->mRemoved = false;
    }

    public function getType()
    {
        return $this->mType;
    }

    public function getCallback()
    {
        return $this->mCallback;
    }

    public function getCapture()
    {
        return $this->mCapture;
    }

    public function getPassive()
    {
        return $this->mPassive;
    }

    public function getOnce()
    {
        return $this->mOnce;
    }

    public function getRemoved()
    {
        return $this->mRemoved;
    }

    public function setRemoved($aRemoved)
    {
        $this->mRemoved = $aRemoved;
    }

    public function isEqual($aOther)
    {
        if ($aOther->mType === $this->mType &&
            $aOther->mCallback === $this->mCallback &&
            $aOther->mCapture === $this->mCapture
        ) {
            return true;
        }

        return false;
    }
}
