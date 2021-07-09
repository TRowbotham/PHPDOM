<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\Node;

/**
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
 */
class HTMLTemplateElement extends HTMLElement
{
    /**
     * @var \Rowbot\DOM\DocumentFragment
     */
    protected $content;

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

    public function doAdoptingSteps(Document $oldDocument): void
    {
        $doc = $this->nodeDocument->getAppropriateTemplateContentsOwnerDocument();
        $doc->doAdoptNode($this->content);
    }

    public function onCloneNode(self $copy, Node $node, Document $document, bool $cloneChildren): void
    {
        if (!$cloneChildren) {
            return;
        }

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
