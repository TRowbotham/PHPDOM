<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-img-element
 */
class HTMLImageElement extends HTMLElement
{
    private $mAlt;
    private $mCrossOrigin;
    private $mHeight;
    private $mIsMap;
    private $mSizes;
    private $mSrc;
    private $mSrcset;
    private $mUseMap;
    private $mWidth;

    protected function __construct()
    {
        parent::__construct();

        $this->mEndTagOmitted = true;
        $this->mAlt = '';
        $this->mCrossOrigin = '';
        $this->mHeight = 0;
        $this->mIsMap = false;
        $this->mSizes = '';
        $this->mSrc = '';
        $this->mSrcset = '';
        $this->mUseMap = '';
        $this->mWidth = 0;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'alt':
                return $this->mAlt;

            case 'crossOrigin':
                return $this->mCrossOrigin;

            case 'height':
                return $this->mHeight;

            case 'isMap':
                return $this->mIsMap;

            case 'sizes':
                return $this->mSizes;

            case 'src':
                return $this->mSrc;

            case 'srcset':
                return $this->mSrcset;

            case 'useMap':
                return $this->mUseMap;

            case 'width':
                return $this->mWidth;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'alt':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mAlt = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'crossOrigin':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mCrossOrigin = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'sizes':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mSizes = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'src':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mSrc = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'srcset':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mSrcset = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'useMap':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mUseMap = $aValue;
                $this->mIsMap = !!$aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'width':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mWidth = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aName);
        }
    }
}
