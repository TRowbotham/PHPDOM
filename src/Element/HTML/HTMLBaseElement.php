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

    public function __get($name)
    {
        switch ($name) {
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
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'href':
            case 'target':
                $this->attributeList->setAttrValue($name, $value);

                break;

            default:
                parent::__set($name, $value);
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
        Element $element,
        $localName,
        $oldValue,
        $value,
        $namespace
    ) {
        if ($localName === 'href' &&
            $namespace === null
        ) {
            $this->setFrozenBaseURL($value);
        } else {
            parent::onAttributeChanged(
                $element,
                $localName,
                $oldValue,
                $value,
                $namespace
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
     * @param string|null $href This value can only be non-null if the method
     *     is called from the onAttributeChanged method since, in the case of
     *     a content attribute being added to the element, the content attribute
     *     has not yet been placed in the element's content attribute list.
     */
    public function setFrozenBaseURL($href = null)
    {
        $document = $this->nodeDocument;
        $fallbackBaseURL = $document->getFallbackBaseURL();
        $urlRecord = false;

        if ($href !== null) {
            $hrefAttr = $this->attributeList->getAttrByNamespaceAndLocalName(
                null,
                'href'
            );

            if ($hrefAttr !== null) {
                // Parse the Element's href attribute.
                $urlRecord = URLParser::parseUrl(
                    $hrefAttr->value,
                    $fallbackBaseURL,
                    $document->characterSet
                );
            }
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
