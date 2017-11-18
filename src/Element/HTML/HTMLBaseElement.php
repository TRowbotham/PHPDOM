<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\URL\URLParser;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-base-element
 */
class HTMLBaseElement extends HTMLElement
{
    private $frozenBaseUrl;

    protected function __construct()
    {
        parent::__construct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'href':
                $document = $this->nodeDocument;
                $url = $this->attributeList->getAttrValue('href', null);
                $urlRecord = URLParser::parseUrl(
                    $url,
                    $document->getFallbackBaseURL(),
                    $document->characterSet
                );

                if ($urlRecord === false) {
                    return $url;
                }

                return $urlRecord->serializeURL();

            case 'target':
                return $this->attributeList->getAttrValue('target', null);

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'href':
            case 'target':
                $this->attributeList->setAttrValue($aName, $aValue);

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
     * @return URLRecord
     */
    public function getFrozenBaseURL()
    {
        return $this->frozenBaseUrl;
    }

    /**
     * @see AttributeChangeObserver
     */
    public function onAttributeChanged(
        Element $aElement,
        $aLocalName,
        $aOldValue,
        $aValue,
        $aNamespace
    ) {
        if ($aLocalName === 'href' &&
            $aNamespace === null
        ) {
            $this->setFrozenBaseURL($aValue);
        } else {
            parent::onAttributeChanged(
                $aElement,
                $aLocalName,
                $aOldValue,
                $aValue,
                $aNamespace
            );
        }
    }

    /**
     * Sets the Element's frozen base URL
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#set-the-frozen-base-url
     *
     * @param string|null $aHref This value can only be non-null if the method
     *     is called from the onAttributeChanged method since, in the case of
     *     a content attribute being added to the element, the content attribute
     *     has not yet been placed in the element's content attribute list.
     */
    public function setFrozenBaseURL($aHref = null)
    {
        $document = $this->nodeDocument;
        $fallbackBaseURL = $document->getFallbackBaseURL();
        $urlRecord = false;

        if ($aHref) {
            $href = $aHref;
        } else {
            $hrefAttr = $this->attributeList->getAttrByNamespaceAndLocalName(
                null,
                'href'
            );
            $href = $hrefAttr ? $hrefAttr->value : null;
        }

        // Don't bother trying to parse a URL if the element does not have an
        // href content attribute.
        if ($href !== null) {
            // Parse the Element's href attribute.
            $urlRecord = URLParser::parseUrl(
                $href,
                $fallbackBaseURL,
                $document->characterSet
            );
        }

        // TODO: Set element's frozen base URL to document's fallback base URL
        // if urlRecord is failure or running Is base allowed for Document? on
        // the resulting URL record and document returns "Blocked"
        if ($urlRecord === false) {
            $this->frozenBaseUrl = $fallbackBaseURL;
        } else {
            $this->frozenBaseUrl = $urlRecord;
        }
    }

    protected function doInsertingSteps()
    {
        if ($this->parentNode instanceof HTMLHeadElement) {
            $node = $this;

            while ($node) {
                $node = $node->previousSibling;
                $isValid = $node instanceof HTMLBaseElement &&
                    $this->attributeList->getAttrByNamespaceAndLocalName(
                        null,
                        'href'
                    );

                if ($isValid) {
                    break;
                }
            }

            if (!$node) {
                $this->setFrozenBaseURL();
            }
        }
    }
}
