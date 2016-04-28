<?php
namespace phpjs\elements\html;

use phpjs\urls\URLInternal;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-base-element
 */
class HTMLBaseElement extends HTMLElement
{
    private $mFrozenBaseURL;

    protected function __construct()
    {
        parent::__construct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'href':
                $document = $this->mOwnerDocument;
                $url = $this->mAttributesList->getAttrValue(
                    $this,
                    'href',
                    null
                );
                $urlRecord = URLInternal::URLParser(
                    $url,
                    $document->getFallbackBaseURL(),
                    $document->characterSet
                );

                if ($urlRecord === false) {
                    return $url;
                }

                return $urlRecord->serializeURL();

            case 'target':
                return $this->mAttributesList->getAttrValue(
                    $this,
                    'target',
                    null
                );

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'href':
            case 'target':
                $this->mAttributesList->setAttrValue(
                    $this,
                    $aName,
                    $aValue
                );

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    /**
     * Gets the Element's frozen base URL.
     *
     * @internal
     *
     * @return URLInternal
     */
    public function getFrozenBaseURL()
    {
        return $this->mFrozenBaseURL;
    }

    /**
     * Sets the Element's frozen base URL
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#set-the-frozen-base-url
     */
    public function setFrozenBaseURL()
    {
        $document = $this->mOwnerDocument;
        $fallbackBaseURL = $document->getFallbackBaseURL();
        $urlRecord = false;
        $href = $this->mAttributesList->getAttrByNamespaceAndLocalName(
            null,
            'href',
            $this
        );

        // Don't bother trying to parse a URL if the element does not have an
        // href content attribute.
        if ($href !== null) {
            // Parse the Element's href attribute.
            $urlRecord = URLInternal::URLParser(
                $href->value,
                $fallbackBaseURL,
                $document->characterSet
            );
        }

        // TODO: Set element's frozen base URL to document's fallback base URL
        // if urlRecord is failure or running Is base allowed for Document? on
        // the resulting URL record and document returns "Blocked"
        if ($urlRecord === false) {
            $this->mFrozenBaseURL = $fallbackBaseURL;
        } else {
            $this->mFrozenBaseURL = $urlRecord;
        }
    }
}
