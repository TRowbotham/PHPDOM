<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\NodeAdoptHook;
use Rowbot\DOM\NodeCloneHook;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentFragment;
use Rowbot\DOM\Node;

use function assert;

/**
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
 *
 * @property-read \Rowbot\DOM\DocumentFragment $content
 */
class HTMLTemplateElement extends HTMLElement implements NodeAdoptHook, NodeCloneHook
{
    protected DocumentFragment $content;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $doc = $this->nodeDocument->getAppropriateTemplateContentsOwnerDocument();
        $this->content = $doc->createDocumentFragment();
        $this->content->setHost($this);
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'content':
                return $this->content;

            default:
                return parent::__get($name);
        }
    }

    public function onAdopt(Node $node, Document $oldDocument): void
    {
        $doc = $node->nodeDocument->getAppropriateTemplateContentsOwnerDocument();
        assert($node instanceof self);
        $doc->doAdoptNode($node->content);
    }

    public function onClone(Node $copy, Node $node, Document $document, bool $cloneChildren = false): void
    {
        if (!$cloneChildren) {
            return;
        }

        assert($copy instanceof self && $node instanceof self);
        $copiedContents = $node->content->cloneNodeInternal($copy->content->nodeDocument, true);
        $copy->content->appendChild($copiedContents);
    }

    protected function __clone()
    {
        parent::__clone();

        $doc = $this->nodeDocument->getAppropriateTemplateContentsOwnerDocument();
        $this->content = $doc->createDocumentFragment();
        $this->content->setHost($this);
    }
}
