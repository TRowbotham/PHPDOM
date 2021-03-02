<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#interface-cdatasection
 */
class CDATASection extends Text
{
    public function __construct(Document $document, string $data)
    {
        parent::__construct($document, $data);

        $this->nodeType = self::CDATA_SECTION_NODE;
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $this->hasEqualChildNodes($otherNode);
    }

    protected function getNodeName(): string
    {
        return '#cdata-section';
    }
}
