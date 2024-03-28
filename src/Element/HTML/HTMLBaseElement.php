<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\InternalEvent\AttributeChangedEvent;
use Rowbot\DOM\InternalEvent\NodeInsertedEvent;
use Rowbot\DOM\InternalEvent\NodeRemovedEvent;
use Rowbot\DOM\URL\URLParser;
use Rowbot\URL\URLRecord;

use function assert;
use function in_array;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-base-element
 */
class HTMLBaseElement extends HTMLElement
{
    private const TARGET_KEYWORDS = ['_self', '_blank', '_parent', '_top'];

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-base-href
     */
    public string $href {
        get {
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
        }
        set {
            $this->attributeList->setAttrValue('href', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-base-target
     */
    public string $target {
        get => $this->attributeList->getAttrValue('target', null);
        set {
            $this->attributeList->setAttrValue('target', $value);
        }
    }

    private ?URLRecord $frozenBaseUrl;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $this->frozenBaseUrl = null;
        $this->dispatcher->addListener('node.inserted', $this->onInsert(...));
        $this->dispatcher->addListener('node.removed', $this->onRemove(...));
        $this->dispatcher->addListener('attribute.changed', $this->onTargetOrHrefAttributeChanged(...));
    }

    /**
     * Gets the Element's frozen base URL.
     *
     * @internal
     */
    public function getFrozenBaseURL(): URLRecord
    {
        assert($this->frozenBaseUrl !== null);

        return $this->frozenBaseUrl;
    }

    public function onTargetOrHrefAttributeChanged(AttributeChangedEvent $event): void
    {
        if ($event->namespace !== null) {
            return;
        }

        assert($event->element === $this);

        $isTarget = $event->localName === 'target';
        $isHref = $event->localName === 'href';
        $targetIsKeyword = false;
        $hasTarget = false;

        if (!$isHref && !$isTarget) {
            return;
        }

        if ($isTarget) {
            $targetIsKeyword = in_array($event->value, self::TARGET_KEYWORDS, true);
            $hasTarget = true;
        }

        if ($isHref) {
            $target = $this->attributeList->getAttrByNamespaceAndLocalName(null, 'target');
            $hasTarget = $target !== null;
            $targetIsKeyword = $target !== null && in_array($target->getValue(), self::TARGET_KEYWORDS, true);
            $event->element->setFrozenBaseURL($event->value);
        }

        $list = $event->element->nodeDocument->getBaseElements();
        $shouldActivate = (!$hasTarget || $targetIsKeyword)
            && $event->element->getRootNode() === $event->element->nodeDocument;

        if ($shouldActivate && $list->getActiveBase() === null) {
            $list->setActiveBase($event->element);
        }
    }

    /**
     * Sets the Element's frozen base URL
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#set-the-frozen-base-url
     *
     * @param string|null $href This value can only be non-null if the method is called from the onAttributeChanged
     *                          method since, in the case of a content attribute being added to the element, the content
     *                          attribute has not yet been placed in the element's content attribute list.
     */
    public function setFrozenBaseURL(?string $href = null): void
    {
        $document = $this->nodeDocument;
        $fallbackBaseURL = $document->getFallbackBaseURL();
        $urlRecord = false;

        if ($href !== null) {
            // Parse the Element's href attribute.
            $urlRecord = URLParser::parseUrl($href, $fallbackBaseURL, $document->characterSet);
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

    public function onInsert(NodeInsertedEvent $event): void
    {
        if ($event->insertedNode->nodeDocument->getBaseElements()->add($event->insertedNode)) {
            $event->insertedNode->setFrozenBaseURL();
        }
    }

    public function onRemove(NodeRemovedEvent $event): void
    {
        $baseElements = $this->nodeDocument->getBaseElements();

        if ($baseElements->remove($this)) {
            assert($baseElements->getActiveBase() !== null);
            $baseElements->getActiveBase()->setFrozenBaseURL();
        }
    }

    protected function __clone()
    {
        parent::__clone();

        if ($this->frozenBaseUrl !== null) {
            $this->frozenBaseUrl = clone $this->frozenBaseUrl;
        }

        $this->dispatcher->addListener('node.inserted', $this->onInsert(...));
        $this->dispatcher->addListener('node.removed', $this->onRemove(...));
    }
}
