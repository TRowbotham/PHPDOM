<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;

/**
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
 */
class HTMLTemplateElement extends HTMLElement
{
    /**
     * @var \Rowbot\DOM\DocumentFragment
     */
    protected $content;

    protected function __construct(Document $document)
    {
        parent::__construct($document);

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

    protected function __clone()
    {
        parent::__clone();

        $this->content = $this->content->cloneNodeInternal($this->content->getNodeDocument(), true);
    }
}
