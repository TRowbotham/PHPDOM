<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Parser\XML\XMLParser;

class XMLSerializer
{
    public function serializeToString(Node $root)
    {
        return XMLParser::serializeAsXML($root, false);
    }
}
