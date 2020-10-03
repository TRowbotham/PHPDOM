<?php

namespace Rowbot\DOM\Tests\dom;

use Rowbot\DOM\Document;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/common.js
 */
trait Common
{
    public static function setupRangeTests(Document $document): void
    {
        global $testDiv, $paras, $detachedDiv, $detachedPara1, $detachedPara2,
        $foreignDoc, $foreignPara1, $foreignPara2, $xmlDoc, $xmlElement,
        $detachedXmlElement, $detachedTextNode, $foreignTextNode,
        $detachedForeignTextNode, $xmlTextNode, $detachedXmlTextNode,
        $processingInstruction, $detachedProcessingInstruction, $comment,
        $detachedComment, $foreignComment, $detachedForeignComment, $xmlComment,
        $detachedXmlComment, $docfrag, $foreignDocfrag, $xmlDocfrag, $doctype,
        $foreignDoctype, $xmlDoctype, $testRangesShort, $testRanges,
        $testPoints, $testNodesShort, $testNodes;

        static $testSetupComplete = false;

        // Tests have already been setup, return.
        if ($testSetupComplete) {
            return;
        }

        $testSetupComplete = true;

        // $testDiv = $document->querySelector('#test');
        $testDiv = $document->getElementById('test');

        if ($testDiv) {
            $testDiv->parentNode->removeChild($testDiv);
        }

        $testDiv = $document->createElement('div');
        $testDiv->id = 'test';
        $document->body->insertBefore($testDiv, $document->body->firstChild);


        $paras = [];
        $paras[] = $document->createElement('p');
        $paras[0]->setAttribute('id', 'a');
        // Test some diacritics, to make sure browsers are using code units here
        // and not something like grapheme clusters.
        $paras[0]->textContent = "A\u{0308}b\u{0308}c\u{0308}d\u{0308}e\u{0308}f\u{0308}g\u{0308}h\u{0308}\n";
        $testDiv->appendChild($paras[0]);

        $paras[] = $document->createElement('p');
        $paras[1]->setAttribute("id", "b");
        $paras[1]->setAttribute("style", "display:none");
        $paras[1]->textContent = "Ijklmnop\n";
        $testDiv->appendChild($paras[1]);

        $paras[] = $document->createElement("p");
        $paras[2]->setAttribute("id", "c");
        $paras[2]->textContent = "Qrstuvwx";
        $testDiv->appendChild($paras[2]);

        $paras[] = $document->createElement("p");
        $paras[3]->setAttribute("id", "d");
        $paras[3]->setAttribute("style", "display:none");
        $paras[3]->textContent = "Yzabcdef";
        $testDiv->appendChild($paras[3]);

        $paras[] = $document->createElement("p");
        $paras[4]->setAttribute("id", "e");
        $paras[4]->setAttribute("style", "display:none");
        $paras[4]->textContent = "Ghijklmn";
        $testDiv->appendChild($paras[4]);

        $detachedDiv = $document->createElement("div");
        $detachedPara1 = $document->createElement("p");
        $detachedPara1->appendChild($document->createTextNode("Opqrstuv"));
        $detachedPara2 = $document->createElement("p");
        $detachedPara2->appendChild($document->createTextNode("Wxyzabcd"));
        $detachedDiv->appendChild($detachedPara1);
        $detachedDiv->appendChild($detachedPara2);

        // Opera doesn't automatically create a doctype for a new HTML document,
        // contrary to spec.  It also doesn't let you add doctypes to documents
        // after the fact through any means I've tried.  So foreignDoc in Opera
        // will have no doctype, foreignDoctype will be null, and Opera will
        // fail some tests somewhat mysteriously as a result.
        $foreignDoc = $document->implementation->createHTMLDocument("");
        $foreignPara1 = $foreignDoc->createElement("p");
        $foreignPara1->appendChild($foreignDoc->createTextNode("Efghijkl"));
        $foreignPara2 = $foreignDoc->createElement("p");
        $foreignPara2->appendChild($foreignDoc->createTextNode("Mnopqrst"));
        $foreignDoc->body->appendChild($foreignPara1);
        $foreignDoc->body->appendChild($foreignPara2);

        // Now we get to do really silly stuff, which nobody in the universe is
        // ever going to actually do, but the spec defines behavior, so too bad.
        // Testing is fun!
        $xmlDoctype = $document->implementation->createDocumentType(
            "qorflesnorf",
            "abcde",
            "x\"'y"
        );
        $xmlDoc = $document->implementation->createDocument(null, null, $xmlDoctype);
        $detachedXmlElement = $xmlDoc->createElement(
            "everyone-hates-hyphenated-element-names"
        );
        $detachedTextNode = $document->createTextNode("Uvwxyzab");
        $detachedForeignTextNode = $foreignDoc->createTextNode("Cdefghij");
        $detachedXmlTextNode = $xmlDoc->createTextNode("Klmnopqr");
        // PIs only exist in XML documents, so don't bother with document or
        // foreignDoc.
        $detachedProcessingInstruction = $xmlDoc->createProcessingInstruction(
            "whippoorwill",
            "chirp chirp chirp"
        );
        $detachedComment = $document->createComment("Stuvwxyz");
        // Hurrah, we finally got to "z" at the end!
        $detachedForeignComment = $foreignDoc->createComment("אריה יהודה");
        $detachedXmlComment = $xmlDoc->createComment("בן חיים אליעזר");

        // We should also test with document fragments that actually contain
        // stuff . . . but, maybe later.
        $docfrag = $document->createDocumentFragment();
        $foreignDocfrag = $foreignDoc->createDocumentFragment();
        $xmlDocfrag = $xmlDoc->createDocumentFragment();

        $xmlElement = $xmlDoc->createElement("igiveuponcreativenames");
        $xmlTextNode = $xmlDoc->createTextNode("do re mi fa so la ti");
        $xmlElement->appendChild($xmlTextNode);
        $processingInstruction = $xmlDoc->createProcessingInstruction("somePI", 'Did you know that ":syn sync fromstart" is very useful when using vim to edit large amounts of JavaScript embedded in HTML?');
        $xmlDoc->appendChild($xmlElement);
        $xmlDoc->appendChild($processingInstruction);
        $xmlComment = $xmlDoc->createComment("I maliciously created a comment that will break incautious XML serializers, but Firefox threw an exception, so all I got was this lousy T-shirt");
        $xmlDoc->appendChild($xmlComment);

        $comment = $document->createComment("Alphabet soup?");
        $testDiv->appendChild($comment);

        $foreignComment = $foreignDoc->createComment('"Commenter" and "commentator" mean different things.  I\'ve seen non-native speakers trip up on this.');
        $foreignDoc->appendChild($foreignComment);
        $foreignTextNode = $foreignDoc->createTextNode("I admit that I harbor doubts about whether we really need so many things to test, but it's too late to stop now.");
        $foreignDoc->body->appendChild($foreignTextNode);

        $doctype = $document->doctype;
        $foreignDoctype = $foreignDoc->doctype;

        $testRangesShort = [
            // Various ranges within the text node children of different
            // paragraphs.  All should be valid.
            '[$paras[0]->firstChild, 0, $paras[0]->firstChild, 0]',
            '[$paras[0]->firstChild, 0, $paras[0]->firstChild, 1]',
            '[$paras[0]->firstChild, 2, $paras[0]->firstChild, 8]',
            '[$paras[0]->firstChild, 2, $paras[0]->firstChild, 9]',
            '[$paras[1]->firstChild, 0, $paras[1]->firstChild, 0]',
            '[$paras[1]->firstChild, 2, $paras[1]->firstChild, 9]',
            '[$detachedPara1->firstChild, 0, $detachedPara1->firstChild, 0]',
            '[$detachedPara1->firstChild, 2, $detachedPara1->firstChild, 8]',
            '[$foreignPara1->firstChild, 0, $foreignPara1->firstChild, 0]',
            '[$foreignPara1->firstChild, 2, $foreignPara1->firstChild, 8]',
            // Now try testing some elements, not just text nodes.
            '[$document->documentElement, 0, $document->documentElement, 1]',
            '[$document->documentElement, 0, $document->documentElement, 2]',
            '[$document->documentElement, 1, $document->documentElement, 2]',
            '[$document->head, 1, $document->head, 1]',
            '[$document->body, 4, $document->body, 5]',
            '[$foreignDoc->documentElement, 0, $foreignDoc->documentElement, 1]',
            '[$paras[0], 0, $paras[0], 1]',
            '[$detachedPara1, 0, $detachedPara1, 1]',
            // Now try some ranges that span elements.
            '[$paras[0]->firstChild, 0, $paras[1]->firstChild, 0]',
            '[$paras[0]->firstChild, 0, $paras[1]->firstChild, 8]',
            '[$paras[0]->firstChild, 3, $paras[3], 1]',
            // How about something that spans a node and its descendant?
            '[$paras[0], 0, $paras[0]->firstChild, 7]',
            '[$testDiv, 2, $paras[4], 1]',
            // Then a few more interesting things just for good measure.
            '[$document, 0, $document, 1]',
            '[$document, 0, $document, 2]',
            '[$comment, 2, $comment, 3]',
            '[$testDiv, 0, $comment, 5]',
            '[$foreignDoc, 1, $foreignComment, 2]',
            '[$foreignDoc->body, 0, $foreignTextNode, 36]',
            '[$xmlDoc, 1, $xmlComment, 0]',
            '[$detachedTextNode, 0, $detachedTextNode, 8]',
            '[$detachedForeignTextNode, 0, $detachedForeignTextNode, 8]',
            '[$detachedXmlTextNode, 0, $detachedXmlTextNode, 8]',
            '[$detachedComment, 3, $detachedComment, 4]',
            '[$detachedForeignComment, 0, $detachedForeignComment, 1]',
            '[$detachedXmlComment, 2, $detachedXmlComment, 6]',
            '[$docfrag, 0, $docfrag, 0]',
            '[$processingInstruction, 0, $processingInstruction, 4]',
        ];

        $testRanges = array_merge($testRangesShort, [
            '[$paras[1]->firstChild, 0, $paras[1]->firstChild, 1]',
            '[$paras[1]->firstChild, 2, $paras[1]->firstChild, 8]',
            '[$detachedPara1->firstChild, 0, $detachedPara1->firstChild, 1]',
            '[$foreignPara1->firstChild, 0, $foreignPara1->firstChild, 1]',
            '[$foreignDoc->head, 1, $foreignDoc->head, 1]',
            '[$foreignDoc->body, 0, $foreignDoc->body, 0]',
            '[$paras[0], 0, $paras[0], 0]',
            '[$detachedPara1, 0, $detachedPara1, 0]',
            '[$testDiv, 1, $paras[2]->firstChild, 5]',
            '[$document->documentElement, 1, $document->body, 0]',
            '[$foreignDoc->documentElement, 1, $foreignDoc->body, 0]',
            '[$document, 1, $document, 2]',
            '[$paras[2]->firstChild, 4, $comment, 2]',
            '[$paras[3], 1, $comment, 8]',
            '[$foreignDoc, 0, $foreignDoc, 0]',
            '[$xmlDoc, 0, $xmlDoc, 0]',
            '[$detachedForeignTextNode, 7, $detachedForeignTextNode, 7]',
            '[$detachedXmlTextNode, 7, $detachedXmlTextNode, 7]',
            '[$detachedComment, 5, $detachedComment, 5]',
            '[$detachedForeignComment, 4, $detachedForeignComment, 4]',
            '[$foreignDocfrag, 0, $foreignDocfrag, 0]',
            '[$xmlDocfrag, 0, $xmlDocfrag, 0]',
        ]);

        $testPoints = [
            // Various positions within the page, some invalid.  Remember that
            // paras[0] is visible, and paras[1] is display: none.
            '[$paras[0]->firstChild, -1]',
            '[$paras[0]->firstChild, 0]',
            '[$paras[0]->firstChild, 1]',
            '[$paras[0]->firstChild, 2]',
            '[$paras[0]->firstChild, 8]',
            '[$paras[0]->firstChild, 9]',
            '[$paras[0]->firstChild, 10]',
            '[$paras[0]->firstChild, 65535]',
            '[$paras[1]->firstChild, -1]',
            '[$paras[1]->firstChild, 0]',
            '[$paras[1]->firstChild, 1]',
            '[$paras[1]->firstChild, 2]',
            '[$paras[1]->firstChild, 8]',
            '[$paras[1]->firstChild, 9]',
            '[$paras[1]->firstChild, 10]',
            '[$paras[1]->firstChild, 65535]',
            '[$detachedPara1->firstChild, 0]',
            '[$detachedPara1->firstChild, 1]',
            '[$detachedPara1->firstChild, 8]',
            '[$detachedPara1->firstChild, 9]',
            '[$foreignPara1->firstChild, 0]',
            '[$foreignPara1->firstChild, 1]',
            '[$foreignPara1->firstChild, 8]',
            '[$foreignPara1->firstChild, 9]',
            // Now try testing some elements, not just text nodes.
            '[$document->documentElement, -1]',
            '[$document->documentElement, 0]',
            '[$document->documentElement, 1]',
            '[$document->documentElement, 2]',
            '[$document->documentElement, 7]',
            '[$document->head, 1]',
            '[$document->body, 3]',
            '[$foreignDoc->documentElement, 0]',
            '[$foreignDoc->documentElement, 1]',
            '[$foreignDoc->head, 0]',
            '[$foreignDoc->body, 1]',
            '[$paras[0], 0]',
            '[$paras[0], 1]',
            '[$paras[0], 2]',
            '[$paras[1], 0]',
            '[$paras[1], 1]',
            '[$paras[1], 2]',
            '[$detachedPara1, 0]',
            '[$detachedPara1, 1]',
            '[$testDiv, 0]',
            '[$testDiv, 3]',
            // Then a few more interesting things just for good measure.
            '[$document, -1]',
            '[$document, 0]',
            '[$document, 1]',
            '[$document, 2]',
            '[$document, 3]',
            '[$comment, -1]',
            '[$comment, 0]',
            '[$comment, 4]',
            '[$comment, 96]',
            '[$foreignDoc, 0]',
            '[$foreignDoc, 1]',
            '[$foreignComment, 2]',
            '[$foreignTextNode, 0]',
            '[$foreignTextNode, 36]',
            '[$xmlDoc, -1]',
            '[$xmlDoc, 0]',
            '[$xmlDoc, 1]',
            '[$xmlDoc, 5]',
            '[$xmlComment, 0]',
            '[$xmlComment, 4]',
            '[$processingInstruction, 0]',
            '[$processingInstruction, 5]',
            '[$processingInstruction, 9]',
            '[$detachedTextNode, 0]',
            '[$detachedTextNode, 8]',
            '[$detachedForeignTextNode, 0]',
            '[$detachedForeignTextNode, 8]',
            '[$detachedXmlTextNode, 0]',
            '[$detachedXmlTextNode, 8]',
            '[$detachedProcessingInstruction, 12]',
            '[$detachedComment, 3]',
            '[$detachedComment, 5]',
            '[$detachedForeignComment, 0]',
            '[$detachedForeignComment, 4]',
            '[$detachedXmlComment, 2]',
            '[$docfrag, 0]',
            '[$foreignDocfrag, 0]',
            '[$xmlDocfrag, 0]',
            '[$doctype, 0]',
            '[$doctype, -17]',
            '[$doctype, 1]',
            '[$foreignDoctype, 0]',
            '[$xmlDoctype, 0]',
        ];

        $testNodesShort = [
            '$paras[0]',
            '$paras[0]->firstChild',
            '$paras[1]->firstChild',
            '$foreignPara1',
            '$foreignPara1->firstChild',
            '$detachedPara1',
            '$detachedPara1->firstChild',
            '$document',
            '$detachedDiv',
            '$foreignDoc',
            '$foreignPara2',
            '$xmlDoc',
            '$xmlElement',
            '$detachedTextNode',
            '$foreignTextNode',
            '$processingInstruction',
            '$detachedProcessingInstruction',
            '$comment',
            '$detachedComment',
            '$docfrag',
            '$doctype',
            '$foreignDoctype',
        ];

        $testNodes = array_merge($testNodesShort, [
            '$paras[1]',
            '$detachedPara2',
            '$detachedPara2->firstChild',
            '$testDiv',
            '$detachedXmlElement',
            '$detachedForeignTextNode',
            '$xmlTextNode',
            '$detachedXmlTextNode',
            '$xmlComment',
            '$foreignComment',
            '$detachedForeignComment',
            '$detachedXmlComment',
            '$foreignDocfrag',
            '$xmlDocfrag',
            '$xmlDoctype',
        ]);
    }

    public function eval(string $string, Document $document)
    {
        global $testDiv, $paras, $detachedDiv, $detachedPara1, $detachedPara2,
        $foreignDoc, $foreignPara1, $foreignPara2, $xmlDoc, $xmlElement,
        $detachedXmlElement, $detachedTextNode, $foreignTextNode,
        $detachedForeignTextNode, $xmlTextNode, $detachedXmlTextNode,
        $processingInstruction, $detachedProcessingInstruction, $comment,
        $detachedComment, $foreignComment, $detachedForeignComment, $xmlComment,
        $detachedXmlComment, $docfrag, $foreignDocfrag, $xmlDocfrag, $doctype,
        $foreignDoctype, $xmlDoctype;

        return eval('return ' . $string . ';');
    }

    /**
     * Returns the furthest ancestor of a Node as defined by the spec.
     */
    public function furthestAncestor($node)
    {
        $root = $node;

        while ($root->parentNode !== null) {
            $root = $root->parentNode;
        }

        return $root;
    }

    /**
     * Index of a node as defined by the spec.
     */
    public function indexOf($node) {
        if (!$node->parentNode) {
            // No preceding sibling nodes, right?
            return 0;
        }

        $i = 0;

        while ($node !== $node->parentNode->childNodes[$i]) {
            $i++;
        }

        return $i;
    }

    public function getTestNodes()
    {
        global $testNodes;

        foreach ($testNodes as $node) {
            foreach ($testNodes as $other) {
                yield [$node, $other];
            }
        }
    }

    public function getTestRanges($document)
    {
        global $testRanges;

        foreach ($testNodes as $a) {
            yield [$a];
        }
    }

    /**
     * Returns the first Node that's after node in tree order, or null if node is
     * the last Node.
     */
    public static function nextNode(Node $node): ?Node
    {
        if ($node->hasChildNodes()) {
            return $node->firstChild;
        }

        return self::nextNodeDescendants($node);
    }

    /**
     * Returns the last Node that's before node in tree order, or null if node is
     * the first Node.
     */
    public static function previousNode(Node $node): ?Node
    {
        if ($node->previousSibling) {
            $node = $node->previousSibling;

            while ($node->hasChildNodes()) {
                $node = $node->lastChild;
            }

            return $node;
        }

        return $node->parentNode;
    }

    /**
     * Returns the next Node that's after node and all its descendants in tree
     * order, or null if node is the last Node or an ancestor of it.
     */
    public static function nextNodeDescendants(Node $node): ?Node
    {
        while ($node && !$node->nextSibling) {
            $node = $node->parentNode;
        }

        if (!$node) {
            return null;
        }

        return $node->nextSibling;
    }

    /**
     * Returns the ownerDocument of the Node, or the Node itself if it's a
     * Document.
     */
    public function ownerDocument(Node $node): Document
    {
        return $node->nodeType === Node::DOCUMENT_NODE
            ? $node
            : $node->ownerDocument;
    }

    /**
     * Returns true if ancestor is an ancestor of descendant, false otherwise.
     */
    public function isAncestor($ancestor, $descendant): bool
    {
        if (!$ancestor || !$descendant) {
            return false;
        }

        while ($descendant && $descendant !== $ancestor) {
            $descendant = $descendant->parentNode;
        }

        return $descendant === $ancestor;
    }

    /**
     * The position of two boundary points relative to one another, as defined by
     * the spec.
     */
    public function getPosition($nodeA, $offsetA, $nodeB, $offsetB) {
        // "If node A is the same as node B, return equal if offset A equals offset
        // B, before if offset A is less than offset B, and after if offset A is
        // greater than offset B."
        if ($nodeA === $nodeB) {
            if ($offsetA === $offsetB) {
                return "equal";
            }
            if ($offsetA < $offsetB) {
                return "before";
            }
            if ($offsetA > $offsetB) {
                return "after";
            }
        }

        // "If node A is after node B in tree order, compute the position of (node
        // B, offset B) relative to (node A, offset A). If it is before, return
        // after. If it is after, return before."
        if ($nodeB->compareDocumentPosition($nodeA) & Node::DOCUMENT_POSITION_FOLLOWING) {
            $pos = $this->getPosition($nodeB, $offsetB, $nodeA, $offsetA);

            if ($pos === "before") {
                return "after";
            }
            if ($pos === "after") {
                return "before";
            }
        }

        // "If node A is an ancestor of node B:"
        if ($nodeB->compareDocumentPosition($nodeA) & Node::DOCUMENT_POSITION_CONTAINS) {
            // "Let child equal node B."
            $child = $nodeB;

            // "While child is not a child of node A, set child to its parent."
            while ($child->parentNode !== $nodeA) {
                $child = $child->parentNode;
            }

            // "If the index of child is less than offset A, return after."
            if ($this->indexOf($child) < $offsetA) {
                return "after";
            }
        }

        // "Return before."
        return "before";
    }

    /**
     * Given an array of endpoint data [start container, start offset, end
     * container, end offset], returns a Range with those endpoints.
     */
    public function rangeFromEndpoints($endpoints): Range
    {
        // If we just use document instead of the ownerDocument of endpoints[0],
        // WebKit will throw on setStart/setEnd.  This is a WebKit bug, but it's in
        // range, not selection, so we don't want to fail anything for it.
        $range = $this->ownerDocument($endpoints[0])->createRange();
        $range->setStart($endpoints[0], $endpoints[1]);
        $range->setEnd($endpoints[2], $endpoints[3]);

        return $range;
    }
}
