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

    protected function getNodeName(): string
    {
        return '#cdata-section';
    }
}
