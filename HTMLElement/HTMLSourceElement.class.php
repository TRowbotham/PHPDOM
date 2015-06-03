<?php
require_once 'HTMLElement.class.php';

class HTMLSourceElement extends HTMLElement {
    // For picture element
    private $mMedia;
    private $mSizes;
    private $mSrcset;

    // For audio and video elements
    private $mSrc;
    private $mType;

	public function __construct($aTagName) {
		parent::__construct($aTagName);

		$this->mEndTagOmitted = true;
        $this->mMedia = '';
        $this->mSizes = '';
        $this->mSrc = '';
        $this->mSrcset = '';
        $this->mType = '';
	}

    public function __get($aName) {
        switch ($aName) {
            case 'media':
                return $this->mMedia;

            case 'sizes':
                return $this->mSizes;

            case 'src':
                return $this->mSrc;

            case 'srcset':
                return $this->mSrcset;

            case 'type':
                return $this->mType;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'media':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mMedia = $aValue;
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

            case 'type':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mType = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aName);
        }
    }
}
