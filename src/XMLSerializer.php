<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Parser\XML\XMLParser;

class XMLSerializer
{
    public function serializeToString(Node $aRoot)
    {
        return XMLParser::serializeAsXML($aRoot, false);
    }
}
