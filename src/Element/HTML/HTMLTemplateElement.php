<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentFragment;
use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\InternalEvent\NodeAdoptedEvent;
use Rowbot\DOM\InternalEvent\NodeClonedEvent;

use function assert;

/**
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
 *
 * @property-read \Rowbot\DOM\DocumentFragment $content
 */
class HTMLTemplateElement extends HTMLElement
{
    #[Getter('content')]
    protected DocumentFragment $content;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $doc = $this->nodeDocument->getAppropriateTemplateContentsOwnerDocument();
        $this->content = $doc->createDocumentFragment();
        $this->content->setHost($this);
        $this->dispatcher->addListener('node.adopted', $this->onAdopt(...));
        $this->dispatcher->addListener('node.cloned', $this->onClone(...));
    }

    public function onAdopt(NodeAdoptedEvent $event): void
    {
        $doc = $event->node->nodeDocument->getAppropriateTemplateContentsOwnerDocument();
        assert($event->node instanceof self);
        $doc->doAdoptNode($event->node->content);
    }

    public function onClone(NodeClonedEvent $event): void
    {
        if (!$event->cloneChildren) {
            return;
        }

        assert($event->copy instanceof self && $event->node instanceof self);
        $copiedContents = $event->node->content->cloneNodeInternal($event->copy->content->nodeDocument, true);
        $event->copy->content->appendChild($copiedContents);
    }

    protected function __clone()
    {
        parent::__clone();

        $doc = $this->nodeDocument->getAppropriateTemplateContentsOwnerDocument();
        $this->content = $doc->createDocumentFragment();
        $this->content->setHost($this);
        $this->dispatcher->addListener('node.adopted', $this->onAdopt(...));
        $this->dispatcher->addListener('node.cloned', $this->onClone(...));
    }
}
