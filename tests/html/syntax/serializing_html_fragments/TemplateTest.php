<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\serializing_html_fragments;

use Rowbot\DOM\Document;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/serializing-html-fragments/template.html
 */
class TemplateTest extends TestCase
{
    public function testTemplateElementIsCorrectlySerialized(): void
    {
        $document = (new HTMLDocument())->implementation->createHTMLDocument();
        $document->body->insertAdjacentHTML('afterbegin', '<div><template><table><td></table></template></div>');
        // $t = $document->querySelector('template');
        $t = $document->getElementsByTagName('template')[0];

        self::assertSame('<table><tbody><tr><td></td></tr></tbody></table>', $t->innerHTML);
        self::assertSame('<template><table><tbody><tr><td></td></tr></tbody></table></template>', $t->parentNode->innerHTML);
    }

    public function testHTMLFragmentSerializationAlgorithmShouldBeAppliedToTheTemplateContent(): void
    {
        $document = new HTMLDocument();
        $t = $document->createElement('template');
        $c = $t->content->appendChild($document->createElementNS('xx', 'div'));
        $c->setAttributeNS(Namespaces::XML, 'xml:lang', 'en-us');
        $c->setAttributeNS('uri2', 'p:attr', 'v');
        $doc = new Document();
        $doc->adoptNode($t->content);
        self::assertSame('<div xml:lang="en-us" p:attr="v"></div>', $t->innerHTML);
    }
}
