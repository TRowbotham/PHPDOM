<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;

/**
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
 */
class HTMLTemplateElement extends HTMLElement
{
    protected $content;

    protected function __construct()
    {
        parent::__construct();

        $doc = $this->nodeDocument
            ->getAppropriateTemplateContentsOwnerDocument();
        $this->content = $doc->createDocumentFragment();
        $this->content->setHost($this);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'content':
                return $this->content;

            default:
                return parent::__get($name);
        }
    }

    public function doAdoptingSteps(Document $oldDocument)
    {
        $doc = $this->nodeDocument
            ->getAppropriateTemplateContentsOwnerDocument();
        $doc->doAdoptNode($this->content);
    }

    public function doCloningSteps(
        HTMLTemplateElement $copy,
        Document $document,
        $cloneChildren
    ) {
        if (!$cloneChildren) {
            return;
        }

        $copiedContents = $this->content->doCloneNode(
            $copy->content->nodeDocument,
            true
        );
        $copy->content->appendChild($copiedContents);
    }
}
