<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-isConnected.html
 */
class NodeIsConnectedTest extends TestCase
{
    use DocumentGetter;

    public function testOrdinaryNodes()
    {
        $document = $this->getHTMLDocument();
        $nodes = [
            $document->createElement('div'),
            $document->createElement('div'),
            $document->createElement('div'),
        ];

        $this->checkNodes([], $nodes);

        // Append nodes[0]
        $document->body->appendChild($nodes[0]);
        $this->checkNodes([$nodes[0]], [$nodes[1], $nodes[2]]);

        // Append nodes[1] and nodes[2] together
        $nodes[1]->appendChild($nodes[2]);
        $this->checkNodes([$nodes[0]], [$nodes[1], $nodes[2]]);

        $nodes[0]->appendChild($nodes[1]);
        $this->checkNodes($nodes, []);

        $nodes[2]->remove();
        $this->checkNodes([$nodes[0], $nodes[1]], [$nodes[2]]);
    }

    public function testIframes()
    {
        $document = $this->getHTMLDocument();
        $nodes = [
            $document->createElement("iframe"),
            $document->createElement("iframe"),
            $document->createElement("iframe"),
            $document->createElement("iframe"),
            $document->createElement("div"),
        ];
        $frames = [
            $nodes[0],
            $nodes[1],
            $nodes[2],
            $nodes[3],
        ];

        $this->checkNodes([], $nodes);

        // Since we cannot append anything to the contentWindow of an iframe
        // before it is appended to the main DOM tree, we append the iframes
        // one after another.
        $document->body->appendChild($nodes[0]);
        $this->checkNodes(
            [$nodes[0]],
            [$nodes[1], $nodes[2], $nodes[3], $nodes[4]]
        );

        $this->markTestIncomplete('We don\'t support iframes yet.');
    }

    public function checkNodes($connectedNodes, $disconnectedNodes)
    {
        foreach ($connectedNodes as $node) {
            $this->assertTrue($node->isConnected);
        }

        foreach ($disconnectedNodes as $node) {
            $this->assertFalse($node->isConnected);
        }
    }
}
