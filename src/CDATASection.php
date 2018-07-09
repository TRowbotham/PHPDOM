<?php
namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#interface-cdatasection
 */
class CDATASection extends Text
{
    /**
     * Constructor.
     *
     * @param string $data
     *
     * @return void
     */
    public function __construct($data)
    {
        parent::__construct($data);

        $this->nodeType = self::CDATA_SECTION_NODE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeName(): string
    {
        return '#cdata-section';
    }
}
