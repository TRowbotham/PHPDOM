<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

trait NonDocumentTypeChildNode
{
    private function getNextElementSibling()
    {
        $node = $this->nextSibling;

        while ($node) {
            if ($node instanceof Element) {
                break;
            }

            $node = $node->nextSibling;
        }

        return $node;
    }

    private function getPreviousElementSibling()
    {
        $node = $this->previousSibling;

        while ($node) {
            if ($node instanceof Element) {
                break;
            }

            $node = $node->previousSibling;
        }

        return $node;
    }
}