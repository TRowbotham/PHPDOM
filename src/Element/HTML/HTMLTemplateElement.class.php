<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;

/**
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
 */
class HTMLTemplateElement extends HTMLElement
{
    protected $mContent;

    protected function __construct()
    {
        parent::__construct();

        $doc = $this->nodeDocument
            ->getAppropriateTemplateContentsOwnerDocument();
        $this->mContent = $doc->createDocumentFragment();
        $this->mContent->setHost($this);
    }

    public function __destruct()
    {
        $this->mContent = null;
        parent::__destruct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'content':
                return $this->mContent;

            default:
                return parent::__get($aName);
        }
    }

    public function doAdoptingSteps(Document $aOldDocument)
    {
        $doc = $this->nodeDocument
            ->getAppropriateTemplateContentsOwnerDocument();
        $doc->doAdoptNode($this->mContent);
    }

    public function doCloningSteps(
        HTMLTemplateElement $aCopy,
        Document $aDocument,
        $aCloneChildren
    ) {
        if (!$aCloneChildren) {
            return;
        }

        $copiedContents = $this->mContent->doCloneNode(
            $aCopy->mContent->nodeDocument,
            true
        );
        $aCopy->mContent->appendChild($copiedContents);
    }
}
