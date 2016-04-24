<?php
namespace phpjs\elements\html;

use phpjs\Document;

/**
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
 */
class HTMLTemplateElement extends HTMLElement
{
    protected $mContent;

    protected function __construct()
    {
        parent::__construct();

        $doc = $this->mOwnerDocument
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
        $doc = $this->mOwnerDocument
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
            $aCopy->mContent->mOwnerDocument,
            true
        );
        $aCopy->mContent->appendChild($copiedContents);
    }
}
