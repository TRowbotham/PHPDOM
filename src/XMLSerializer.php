<?php
namespace phpjs;

use phpjs\parser\xml\XMLParser;

class XMLSerializer
{
    public function __construct()
    {
    }

    public function serializeToString(Node $aRoot)
    {
        return XMLParser::serializeAsXML($aRoot, false);
    }
}
