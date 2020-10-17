<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InUseAttributeError;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\NamespaceError;
use Rowbot\DOM\NamedNodeMap;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/attributes.html
 */
class AttributesTest extends NodeTestCase
{
    use Attributes;
    use Productions;
    use WindowTrait;

    // toggleAttribute exhaustive tests
    // Step 1
    /**
     * @dataProvider invalidNamesProvider
     */
    public function testQualifiedNameNameDoesNotMatchProductionToggleAttribute(string $invalidName): void
    {
        $document = self::getWindow()->document;
        $el = $document->createElement('foo');

        $this->assertThrows(static function () use ($el, $invalidName) {
            $el->toggleAttribute($invalidName, true);
        }, InvalidCharacterError::class);
        $this->assertThrows(static function () use ($el, $invalidName) {
            $el->toggleAttribute($invalidName);
        }, InvalidCharacterError::class);
        $this->assertThrows(static function () use ($el, $invalidName) {
            $el->toggleAttribute($invalidName, false);
        }, InvalidCharacterError::class);
    }

    /**
     * @dataProvider childElementForTest2Provider
     */
    public function testQualifiedNameNameDoesNotMatchProductionWhenAttrIsPresentToggleAttribute(Element $child): void
    {
        $this->assertThrows(static function () use ($child) {
            $child->toggleAttribute('~', false);
        }, InvalidCharacterError::class);
        $this->assertThrows(static function () use ($child) {
            $child->toggleAttribute('~');
        }, InvalidCharacterError::class);
        $this->assertThrows(static function () use ($child) {
            $child->toggleAttribute('~', true);
        }, InvalidCharacterError::class);
    }

    // Setp 2
    public function testToggleAttributeShouldLowercaseNameArgWithUppercase(): void
    {
        $el = self::getWindow()->document->createElement('div');

        $this->assertTrue($el->toggleAttribute('ALIGN'));
        $this->assertTrue(!$el->hasAttributeNS('', 'ALIGN'));
        $this->assertTrue($el->hasAttributeNS('', 'align'));
        $this->assertTrue($el->hasAttribute('align'));
        $this->assertTrue(!$el->toggleAttribute('ALIGN'));
        $this->assertTrue(!$el->hasAttributeNS('', 'ALIGN'));
        $this->assertTrue(!$el->hasAttributeNS('', 'align'));
        $this->assertTrue(!$el->hasAttribute('align'));
    }

    public function testToggleAttributeShouldLowercaseNameArgWithMixedCase(): void
    {
        $el = self::getWindow()->document->createElement('div');

        $this->assertTrue($el->toggleAttribute('CHEEseCaKe'));
        $this->assertTrue(!$el->hasAttributeNS('', 'CHEEseCaKe'));
        $this->assertTrue($el->hasAttributeNS('', 'cheesecake'));
        $this->assertTrue($el->hasAttribute('cheesecake'));
    }

    // Step 3
    /**
     * @dataProvider xmlnsNameProvider
     */
    public function testToggleAttributeShouldNotThrowIfQualifiedNameStartsWithStringXmlns(string $name): void
    {
        $el = self::getWindow()->document->createElement('foo');

        $this->assertTrue($el->toggleAttribute($name));
        $this->assertTrue($el->hasAttribute($name));
    }

    // Step 4
    /**
     * @dataProvider validNamesProvider
     */
    public function testToggleAttributeBasicFunctionality(string $validName): void
    {
        $el = self::getWindow()->document->createElement('foo');

        $this->assertTrue($el->toggleAttribute($validName));
        $this->assertTrue($el->hasAttribute($validName));
        $this->assertTrue(!$el->toggleAttribute($validName));
        $this->assertTrue(!$el->hasAttribute($validName));
        // Check using force attr
        $this->assertTrue($el->toggleAttribute($validName, true));
        $this->assertTrue($el->hasAttribute($validName));
        $this->assertTrue($el->toggleAttribute($validName, true));
        $this->assertTrue($el->hasAttribute($validName));
        $this->assertTrue(!$el->toggleAttribute($validName, false));
        $this->assertTrue(!$el->hasAttribute($validName));
    }

    // Step 5
    public function testToggleAttributeShouldNotChangeTheOrderOfPreviouslySetAttributes(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->toggleAttribute('a');
        $el->toggleAttribute('b');
        $el->setAttribute('a', 'thing');
        $el->toggleAttribute('c');
        $this->attributes_are($el, [
            ['a', 'thing'],
            ['b', ''],
            ['c', ''],
        ]);
    }

    public function testToggleAttributeShouldSetTheFirstAttributeWithTheGivenName(): void
    {
        $el = self::getWindow()->document->createElement('baz');
        $el->setAttributeNS('ab', 'attr', 'fail');
        $el->setAttributeNS('kl', 'attr', 'pass');
        $el->toggleAttribute('attr');
        $this->attributes_are($el, [['attr', 'pass', 'kl']]);
    }

    public function testToggleAttributeShouldSetTheAttributeWithTheGivenQualifiedName(): void
    {
        // Based on a test by David Flanagan.
        $el = self::getWindow()->document->createElement('baz');
        $el->setAttributeNS('foo', 'foo:bar', '1');
        $el->setAttributeNS('foo', 'foo:bat', '2');
        $this->assertSame('1', $el->getAttribute('foo:bar'));
        $this->assertSame('2', $el->getAttribute('foo:bat'));
        $this->attr_is($el->attributes[0], '1', 'bar', 'foo', 'foo', 'foo:bar');
        $this->attr_is($el->attributes[1], '2', 'bat', 'foo', 'foo', 'foo:bat');
        $el->toggleAttribute('foo:bar');
        $this->assertTrue(!$el->hasAttribute('foo:bar'));
        $this->attr_is($el->attributes[0], '2', 'bat', 'foo', 'foo', 'foo:bat');
    }

    public function testTogglingElementWithInlineStyleShouldMakeInlineStyleDisappear(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->style = "color: red; background-color: green";
        $this->assertFalse($el->toggleAttribute('style'));
    }

    // setAttribute exhaustive tests
    // Step 1
    /**
     * @dataProvider invalidNamesProvider
     */
    public function testSetAttributeThrowsWhenQualifiedNameDoesNotMatchNameProduction(string $name): void
    {
        $this->expectException(InvalidCharacterError::class);
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttribute($name, 'test');
    }

    /**
     * @dataProvider childElementForTest2Provider
     */
    public function testSetAttributeThrowsWhenQualifiedNameDoesNotMatchNameProductionIfAttrExists(Element $el): void
    {
        $this->expectException(InvalidCharacterError::class);
        $el = self::getWindow()->document->getElementById('test2');
        $el->setAttribute('~', 'test');
    }

    // Step 2
    public function testSetAttributeShouldLowercaseNameArgWhenUppercase(): void
    {
        $el = self::getWindow()->document->createElement('div');
        $el->setAttribute('ALIGN', 'left');
        $this->assertNull($el->getAttributeNS('', 'ALIGN'));
        $this->assertSame('left', $el->getAttributeNS('', 'align'));
        $this->assertSame('left', $el->getAttribute('align'));
    }

    public function testSetAttributeShouldLowercaseNameArgWhenMixedCase(): void
    {
        $el = self::getWindow()->document->createElement('div');
        $el->setAttribute('CHEEseCaKe', 'tasty');
        $this->assertNull($el->getAttributeNS('', 'CHEEseCaKe'));
        $this->assertSame('tasty', $el->getAttributeNS('', 'cheesecake'));
        $this->assertSame('tasty', $el->getAttribute('cheesecake'));
    }

    // Step 3
    /**
     * @dataProvider xmlnsNameProvider
     */
    public function testSetAttributeShouldNotThrowWhenQualifiedNameStartsWithStringXmlns(string $name): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttribute($name, 'success');
        $this->assertSame('success', $el->getAttribute($name));
    }

    // Step 4
    /**
     * @dataProvider validNamesProvider
     */
    public function testSetAttributeBasicFunctionality(string $name): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttribute($name, 'test');
        $this->assertSame('test', $el->getAttribute($name));
    }

    // Step 5
    public function testSetAttributeShouldNotChangeTheOrderOfPreviouslySetAttributes(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttribute("a", "1");
        $el->setAttribute("b", "2");
        $el->setAttribute("a", "3");
        $el->setAttribute("c", "4");
        $this->attributes_are($el, [
            ["a", "3"],
            ["b", "2"],
            ["c", "4"],
        ]);
    }

    public function testSetAttributeShouldSetTheFirstAttributeWithTheGivenName(): void
    {
        $el = self::getWindow()->document->createElement("baz");
        $el->setAttributeNS("ab", "attr", "fail");
        $el->setAttributeNS("kl", "attr", "pass");
        $el->setAttribute("attr", "pass");
        $this->attributes_are($el, [
            ["attr", "pass", "ab"],
            ["attr", "pass", "kl"],
        ]);
    }

    public function testSetAttributeShouldSetTheAttributeWithTheGivenQualifiedName(): void
    {
        // Based on a test by David Flanagan.
        $el = self::getWindow()->document->createElement("baz");
        $el->setAttributeNS("foo", "foo:bar", "1");
        $this->assertSame("1", $el->getAttribute("foo:bar"));
        $this->attr_is($el->attributes[0], "1", "bar", "foo", "foo", "foo:bar");
        $el->setAttribute("foo:bar", "2");
        $this->assertSame("2", $el->getAttribute("foo:bar"));
        $this->attr_is($el->attributes[0], "2", "bar", "foo", "foo", "foo:bar");
    }

    // setAttributeNS exhaustive tests
    // Step 1
    /**
     * @dataProvider invalidNamesProvider
     */
    public function testSetAttributeNSShouldThrowWhenQualifiedNameDoesNotMatchNameProduction(string $invalidName): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $this->expectException(InvalidCharacterError::class);
        $el->setAttributeNS('a', $invalidName, 'fail');
    }

    /**
     * @dataProvider childElementForTest2Provider
     */
    public function testSetAttributeNSShouldThrowWhenQualifiedNameDoesNotMatchNameProductionIfAttrExists(Element $el): void
    {
        $this->expectException(InvalidCharacterError::class);
        $el->setAttributeNS(null, '~', 'test');
    }

    // Step 2
    /**
     * @dataProvider invalidNamesProvider
     */
    public function testSetAttributeNSShouldThrowWhenQualifiedNameDoesNotMatchQNameProduction(string $invalidName): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $this->expectException(InvalidCharacterError::class);
        $el->setAttributeNS('a', $invalidName, 'fail');
    }

    // Step 3
    public function testSetAttributeNSNullAndEmptyStringShouldResultInNullNamespace(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttributeNS(null, 'aa', 'bb');
        $el->setAttributeNS('', 'xx', 'bb');
        $this->attributes_are($el, [
            ['aa', 'bb'],
            ['xx', 'bb'],
        ]);
    }

    // Step 4
    /**
     * @testWith ["", "aa:bb", "fail"]
     *           [null, "aa:bb", "fail"]
     */
    public function testSetAttributeNSANamespaceIsRequiredToUseAPrefix(?string $namespace, string $name, string $value): void
    {
        $this->expectException(NamespaceError::class);
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttributeNS($namespace, $name, $value);
    }

    // Step 5
    public function testSetAttributeNSTheXmlPrefixShouldNotBeAllowedForArbitraryNamespaces(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $this->expectException(NamespaceError::class);
        $el->setAttributeNS('a', 'xml:bb', 'fail');
    }

    public function testSetAttributeNSXmlNamspacedAttributesDontNeedAnXmlPrefix(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttributeNS(Namespaces::XML, 'a:bb', 'pass');
        $this->assertCount(1, $el->attributes);
        $this->attr_is($el->attributes[0], 'pass', 'bb', Namespaces::XML, 'a', 'a:bb');
    }

    // Step 6
    public function testSetAttributeNSWithXmlnsPrefixShouldNotBeAllowedForArbitraryNamespaces(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $this->expectException(NamespaceError::class);
        $el->setAttributeNS('a', 'xmlns:bb', 'fail');
    }

    public function testSetAttributeNSWithXmlnsQualifiedNameShouldNotBeAllowedForArbitraryNamespaces(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $this->expectException(NamespaceError::class);
        $el->setAttributeNS('a', 'xmlns', 'fail');
    }

    public function testSetAttributeNSWithXmlnsShouldBeAllowedAsLocalName(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttributeNS('ns', 'a:xmlns', 'pass');
        $this->assertSame(1, $el->attributes->length);
        $this->attr_is($el->attributes[0], 'pass', 'xmlns', 'ns', 'a', 'a:xmlns');
    }

    // Step 7
    public function testSetAttributeNSWithXmlnsNamespaceShouldRequireXmlnsAsPrefixOrQualifiedName(): void
    {
        $el = self::getWindow()->document->createElement('foo');

        $this->assertThrows(static function () use ($el): void {
            $el->setAttributeNS(Namespaces::XMLNS, 'a:xmlns', 'fail');
        }, NamespaceError::class);
        $this->assertThrows(static function () use ($el): void {
            $el->setAttributeNS(Namespaces::XMLNS, 'b:foo', 'fail');
        }, NamespaceError::class);
    }

    public function testSetAttributeNSWithXmlnsShouldBeAllowedAsPrefixInTheXmlnsNamespace(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttributeNS(Namespaces::XMLNS, 'xmlns:a', 'pass');
        $this->assertSame(1, $el->attributes->length);
        $this->attr_is($el->attributes[0], 'pass', 'a', Namespaces::XMLNS, 'xmlns', 'xmlns:a');
    }

    public function testSetAttributeNSWithXmlnsShouldBeAllowedAsQualifiedNameInTheXmlnsNamespace(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttributeNS(Namespaces::XMLNS, 'xmlns', 'pass');
        $this->assertSame(1, $el->attributes->length);
        $this->attr_is($el->attributes[0], 'pass', 'xmlns', Namespaces::XMLNS, null, 'xmlns');
    }

    // Step 8-9
    public function testSetAttributeNSSettingSameAttributeWithAnotherPrefixShouldNotChangeThePrefix(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttributeNS('a', 'foo:bar', 'X');
        $this->assertSame(1, $el->attributes->length);
        $this->attr_is($el->attributes[0], 'X', 'bar', 'a', 'foo', 'foo:bar');

        $el->setAttributeNS('a', 'quux:bar', 'Y');
        $this->assertSame(1, $el->attributes->length);
        $this->attr_is($el->attributes[0], 'Y', 'bar', 'a', 'foo', 'foo:bar');
    }

    // Miscellaneous tests
    public function testSetAttributeShouldNotThrowEvenIfALoadIsNotAllowed(): void
    {
        $el = self::getWindow()->document->createElement('iframe');
        $el->setAttribute('src', 'file:///home');
        $this->assertSame('file:///home', $el->getAttribute('src'));
    }

    public function testAttributesShouldWorkInDocumentFragments(): void
    {
        $document = self::getWindow()->document;
        $docFragment = $document->createDocumentFragment();
        $newOne = $document->createElement('newElement');
        $newOne->setAttribute('newdomestic', 'Yes');
        $docFragment->appendChild($newOne);
        $domesticNode = $docFragment->firstChild;
        $attr = $domesticNode->attributes->item(0);
        $this->attr_is($attr, 'Yes', 'newdomestic', null, null, 'newdomestic');
    }

    public function testAttributeValuesShouldNotBeParsed(): void
    {
        $el = self::getWindow()->document->createElement('foo');
        $el->setAttribute('x', 'y');
        $attr = $el->attributes[0];
        $attr->value = 'Y&lt;';
        $this->attr_is($attr, 'Y&lt;', 'x', null, null, 'x');
        $this->assertSame('Y&lt;', $el->getAttribute('x'));
    }

    public function testSpecifiedAttributesShouldBeAccessible(): void
    {
        $el = self::getWindow()->document->getElementsByTagName('span')[0];
        $this->attr_is($el->attributes[0], 'test1', 'id', null, null, 'id');
    }

    public function testEntitiesInAttributesShouldHaveBeenExpandedWhileParsing(): void
    {
        $el = self::getWindow()->document->getElementsByTagName('span')[1];
        $this->attr_is($el->attributes[0], '&<>foo', 'class', null, null, 'class');
    }

    public function testUnsetAttributesReturnNull(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $this->assertFalse($el->hasAttribute("bar"));
        $this->assertFalse($el->hasAttributeNS(null, "bar"));
        $this->assertFalse($el->hasAttributeNS("", "bar"));
        $this->assertNull($el->getAttribute("bar"));
        $this->assertNull($el->getAttributeNS(null, "bar"));
        $this->assertNull($el->getAttributeNS("", "bar"));
    }

    public function testFirstSetAttributeIsReturnByGetAttribute(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttributeNS("ab", "attr", "t1");
        $el->setAttributeNS("kl", "attr", "t2");
        $this->assertTrue($el->hasAttribute("attr"));
        $this->assertTrue($el->hasAttributeNS("ab", "attr"));
        $this->assertTrue($el->hasAttributeNS("kl", "attr"));
        $this->assertSame("t1", $el->getAttribute("attr"));
        $this->assertSame("t1", $el->getAttributeNS("ab", "attr"));
        $this->assertSame("t2", $el->getAttributeNS("kl", "attr"));
        $this->assertNull($el->getAttributeNS(null, "attr"));
        $this->assertNull($el->getAttributeNS("", "attr"));
    }

    public function testStyleAttributesAreNotNormalized(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttribute("style", "color:#fff;");
        $this->assertTrue($el->hasAttribute("style"));
        $this->assertTrue($el->hasAttributeNS(null, "style"));
        $this->assertTrue($el->hasAttributeNS("", "style"));
        $this->assertSame("color:#fff;", $el->getAttribute("style"));
        $this->assertSame("color:#fff;", $el->getAttributeNS(null, "style"));
        $this->assertSame("color:#fff;", $el->getAttributeNS("", "style"));
    }

    public function testOnlyLowercaseAttributesAreReturnedOnHTMLElementsUpperCase(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttributeNS("", "ALIGN", "left");
        $this->assertFalse($el->hasAttribute("ALIGN"));
        $this->assertFalse($el->hasAttribute("align"));
        $this->assertTrue($el->hasAttributeNS(null, "ALIGN"));
        $this->assertFalse($el->hasAttributeNS(null, "align"));
        $this->assertTrue($el->hasAttributeNS("", "ALIGN"));
        $this->assertFalse($el->hasAttributeNS("", "align"));
        $this->assertNull($el->getAttribute("ALIGN"));
        $this->assertNull($el->getAttribute("align"));
        $this->assertSame("left", $el->getAttributeNS(null, "ALIGN"));
        $this->assertSame("left", $el->getAttributeNS("", "ALIGN"));
        $this->assertNull($el->getAttributeNS(null, "align"));
        $this->assertNull($el->getAttributeNS("", "align"));
        $el->removeAttributeNS("", "ALIGN");
    }

    public function testOnlyLowercaseAttributesAreReturnedOnHTMLElementsMixedCase(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttributeNS("", "CHEEseCaKe", "tasty");
        $this->assertFalse($el->hasAttribute("CHEESECAKE"));
        $this->assertFalse($el->hasAttribute("CHEEseCaKe"));
        $this->assertFalse($el->hasAttribute("cheesecake"));
        $this->assertFalse($el->hasAttributeNS("", "CHEESECAKE"));
        $this->assertTrue($el->hasAttributeNS("", "CHEEseCaKe"));
        $this->assertFalse($el->hasAttributeNS("", "cheesecake"));
        $this->assertFalse($el->hasAttributeNS(null, "CHEESECAKE"));
        $this->assertTrue($el->hasAttributeNS(null, "CHEEseCaKe"));
        $this->assertFalse($el->hasAttributeNS(null, "cheesecake"));
        $this->assertNull($el->getAttribute("CHEESECAKE"));
        $this->assertNull($el->getAttribute("CHEEseCaKe"));
        $this->assertNull($el->getAttribute("cheesecake"));
        $this->assertNull($el->getAttributeNS(null, "CHEESECAKE"));
        $this->assertNull($el->getAttributeNS("", "CHEESECAKE"));
        $this->assertSame("tasty", $el->getAttributeNS(null, "CHEEseCaKe"));
        $this->assertSame("tasty", $el->getAttributeNS("", "CHEEseCaKe"));
        $this->assertNull($el->getAttributeNS(null, "cheesecake"));
        $this->assertNull($el->getAttributeNS("", "cheesecake"));
        $el->removeAttributeNS("", "CHEEseCaKe");
    }

    public function testFirstSetAttributeIsReturnedWithMappedAttributeSetFirst(): void
    {
        $document = self::getWindow()->document;
        $el = $document->createElement("div");
        $document->body->appendChild($el);
        $el->setAttributeNS("", "align", "left");
        $el->setAttributeNS("xx", "align", "right");
        $el->setAttributeNS("", "foo", "left");
        $el->setAttributeNS("xx", "foo", "right");
        $this->assertTrue($el->hasAttribute("align"));
        $this->assertTrue($el->hasAttribute("foo"));
        $this->assertTrue($el->hasAttributeNS("xx", "align"));
        $this->assertTrue($el->hasAttributeNS(null, "foo"));
        $this->assertSame("left", $el->getAttribute("align"));
        $this->assertSame("left", $el->getAttribute("foo"));
        $this->assertSame("right", $el->getAttributeNS("xx", "align"));
        $this->assertSame("left", $el->getAttributeNS(null, "foo"));
        $this->assertSame("left", $el->getAttributeNS("", "foo"));
        $el->removeAttributeNS("", "align");
        $el->removeAttributeNS("xx", "align");
        $el->removeAttributeNS("", "foo");
        $el->removeAttributeNS("xx", "foo");
        $document->body->removeChild($el);
    }

    public function testFirstSetAttributeIsReturnedWithMappedAttributeSetLater(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttributeNS("xx", "align", "right");
        $el->setAttributeNS("", "align", "left");
        $el->setAttributeNS("xx", "foo", "right");
        $el->setAttributeNS("", "foo", "left");
        $this->assertTrue($el->hasAttribute("align"));
        $this->assertTrue($el->hasAttribute("foo"));
        $this->assertTrue($el->hasAttributeNS("xx", "align"));
        $this->assertTrue($el->hasAttributeNS(null, "foo"));
        $this->assertSame("right", $el->getAttribute("align"));
        $this->assertSame("right", $el->getAttribute("foo"));
        $this->assertSame("right", $el->getAttributeNS("xx", "align"));
        $this->assertSame("left", $el->getAttributeNS(null, "foo"));
        $this->assertSame("left", $el->getAttributeNS("", "foo"));
        $el->removeAttributeNS("", "align");
        $el->removeAttributeNS("xx", "align");
        $el->removeAttributeNS("", "foo");
        $el->removeAttributeNS("xx", "foo");
    }

    public function testNonHtmlElementWithUpperCaseAttribtue(): void
    {
        $el = self::getWindow()->document->createElementNS("http://www.example.com", "foo");
        $el->setAttribute("A", "test");
        $this->assertTrue($el->hasAttribute("A"), "hasAttribute()");
        $this->assertTrue($el->hasAttributeNS("", "A"));
        $this->assertTrue($el->hasAttributeNS(null, "A"));
        // $this->assertTrue($el->hasAttributeNS(undefined, "A"));
        $this->assertFalse($el->hasAttributeNS("foo", "A"));

        $this->assertSame("test", $el->getAttribute("A"));
        $this->assertSame("test", $el->getAttributeNS("", "A"));
        $this->assertSame("test", $el->getAttributeNS(null, "A"));
        // $this->assertSame("test", $el->getAttributeNS(undefined, "A"));
        $this->assertNull($el->getAttributeNS("foo", "A"));
    }

    public function testAttributeWithPrefixInLocalName(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttribute("pre:fix", "value 1");
        $el->setAttribute("fix", "value 2");

        $prefixed = $el->attributes[0];
        $this->assertSame("pre:fix", $prefixed->localName);
        $this->assertNull($prefixed->namespaceURI);

        $unprefixed = $el->attributes[1];
        $this->assertSame("fix", $unprefixed->localName);
        $this->assertNull($unprefixed->namespaceURI);

        $el->removeAttributeNS(null, "pre:fix");
        $this->assertSame($el->attributes[0], $unprefixed);
    }

    public function testAttributeLosesItsOwnerWhenRemoved(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttribute("foo", "bar");
        $attr = $el->attributes[0];
        $this->assertSame($el, $attr->ownerElement);
        $el->removeAttribute("foo");
        $this->assertNull($attr->ownerElement);
    }

    public function testGetAttributeNodeAndGetAttributeNodeNSBasicFunctionality(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttribute("foo", "bar");
        $attr = $el->attributes[0];
        $attrNode = $el->getAttributeNode("foo");
        $attrNodeNS = $el->getAttributeNodeNS("", "foo");
        $this->assertSame($attr, $attrNode);
        $this->assertSame($attr, $attrNodeNS);
        $el->setAttributeNS("x", "foo2", "bar2");
        $attr2 = $el->attributes[1];
        $attrNodeNS2 = $el->getAttributeNodeNS("x", "foo2");
        $this->assertSame($attr2, $attrNodeNS2);
    }

    public function testSetAttributeNodeBasicFunctionality(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $el->setAttribute("foo", "bar");
        $attrNode = $el->getAttributeNode("foo");
        $attrNodeNS = $el->getAttributeNodeNS("", "foo");
        $this->assertSame($attrNode, $attrNodeNS);
        $el->removeAttribute("foo");
        $el2 = $document->createElement("div");
        $el2->setAttributeNode($attrNode);
        $this->assertSame($attrNode, $el2->getAttributeNode("foo"));
        $this->assertSame($attrNode, $el2->attributes[0]);
        $this->assertSame($el2, $attrNode->ownerElement);
        $this->assertSame("bar", $attrNode->value);

        $el3 = $document->createElement("div");
        $el2->removeAttribute("foo");
        $el3->setAttribute("foo", "baz");
        $el3->setAttributeNode($attrNode);
        $this->assertSame("bar", $el3->getAttribute("foo"));
    }

    public function testSetAttributeNodeShouldDistinguishAttributesWithSameLocalNameAndDifferentNamespaces(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $attr1 = $document->createAttributeNS("ns1", "p1:name");
        $attr1->value = "value1";
        $attr2 = $document->createAttributeNS("ns2", "p2:name");
        $attr2->value = "value2";
        $el->setAttributeNode($attr1);
        $el->setAttributeNode($attr2);
        $this->assertSame("value1", $el->getAttributeNodeNS("ns1", "name")->value);
        $this->assertSame("value2", $el->getAttributeNodeNS("ns2", "name")->value);
    }

    public function testSetAttributeNodeDoesntHaveCaseInsensitivityEvenWithAnHtmlElement(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $attr1 = $document->createAttributeNS("ns1", "p1:name");
        $attr1->value = "value1";
        $attr2 = $document->createAttributeNS("ns1", "p1:NAME");
        $attr2->value = "VALUE2";
        $el->setAttributeNode($attr1);
        $el->setAttributeNode($attr2);
        $this->assertSame("value1", $el->getAttributeNodeNS("ns1", "name")->value);
        $this->assertSame("VALUE2", $el->getAttributeNodeNS("ns1", "NAME")->value);
    }

    public function testSetAttributeNodeNSBasicFunctionality(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $el->setAttributeNS("x", "foo", "bar");
        $attrNode = $el->getAttributeNodeNS("x", "foo");
        $el->removeAttribute("foo");
        $el2 = $document->createElement("div");
        $el2->setAttributeNS("x", "foo", "baz");
        $el2->setAttributeNodeNS($attrNode);
        $this->assertSame("bar", $el2->getAttributeNS("x", "foo"));
    }

    public function testSetAttributeNodeThrowsIfOwnerElementIsNotNullOrElement(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $other = $document->createElement("div");
        $attr = $document->createAttribute("foo");
        $this->assertNull($el->setAttributeNode($attr));
        $this->assertSame($el, $attr->ownerElement);
        $this->expectException(InUseAttributeError::class);
        $other->setAttributeNode($attr);
    }

    public function testReplacingAttributeWithItself(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $attr = $document->createAttribute("foo");
        $this->assertNull($el->setAttributeNode($attr));
        $el->setAttribute("bar", "qux");
        $this->assertSame($attr, $el->setAttributeNode($attr));
        $this->assertSame($attr, $el->attributes[0]);
    }

    public function testRemoveAttributeNodeBasicFunctionality(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $el->setAttribute("foo", "bar");
        $attrNode = $el->getAttributeNode("foo");
        $el->removeAttributeNode($attrNode);
        $el2 = $document->createElement("div");
        $el2->setAttributeNode($attrNode);
        $this->assertSame($attrNode, $el2->attributes[0]);
        $this->assertSame(0, $el->attributes->length);
    }

    public function testSetAttributeNodeOnBoundAttributeShouldThrow(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $el->setAttribute("foo", "bar");
        $attrNode = $el->getAttributeNode("foo");
        $el2 = $document->createElement("div");
        $this->expectException(InUseAttributeError::class);
        $el2->setAttributeNode($attrNode);
    }

    public function testSetAttributeNodeFiresOneMutationEvent(): void
    {
        $this->markTestSkipped('We don\'t fire mutation events.');

        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $attrNode1 = $document->createAttribute("foo");
        $attrNode1->value = "bar";
        $el->setAttributeNode($attrNode1);
        $attrNode2 = $document->createAttribute("foo");
        $attrNode2->value = "baz";

        $el->addEventListener("DOMAttrModified", function ($e): void {
            // If this never gets called, that's OK, I guess.  But if it gets called, it
            // better represent a single modification with attrNode2 as the relatedNode.
            // We have to do an inner test() call here, because otherwise the exceptions
            // our asserts trigger will get swallowed by the event dispatch code.
            $this->assertSame("foo", $e->attrName);
            $this->assertSame(MutationEvent::MODIFICATION, $e->attrChange);
            $this->assertSame("bar", $e->prevValue);
            $this->assertSame("baz", $e->newValue);
            $this->assertSame($attrNode2, $e->relatedNode);
        });

        $oldNode = $el->setAttributeNode($attrNode2);
        $this->assertSame($attrNode1, $oldNode);
    }

    public function testSetAttributeNodeCalledWithAnAttrThatHasSameNameAsExistingOneShouldNotChangeAttributeOrder(): void
    {
        $document = self::getWindow()->document;

        $el = $document->createElement("div");
        $el->setAttribute("a", "b");
        $el->setAttribute("c", "d");
        $namesToArray = static function (NamedNodeMap $attributes): array {
            $names = [];

            foreach ($attributes as $attribute) {
                $names[] = $attribute->name;
            }

            return $names;
        };
        $valuesToArray = static function (NamedNodeMap $attributes): array {
            $values = [];

            foreach ($attributes as $attribute) {
                $values[] = $attribute->value;
            }

            return $values;
        };

        $this->assertSame(["a", "c"], $namesToArray($el->attributes));
        $this->assertSame(["b", "d"], $valuesToArray($el->attributes));

        $attrNode = $document->createAttribute("a");
        $attrNode->value = "e";
        $el->setAttributeNode($attrNode);

        $this->assertSame(["a", "c"], $namesToArray($el->attributes));
        $this->assertSame(["e", "d"], $valuesToArray($el->attributes));
    }

    public function testGetAttributeName(): void
    {
        $el = self::getWindow()->document->createElement("div");
        $el->setAttribute("foo", "bar");
        $this->assertCount(1, $el->getAttributeNames());
        $this->assertSame($el->attributes[0]->name, $el->getAttributeNames()[0]);
        $this->assertSame("foo", $el->getAttributeNames()[0]);

        $el->removeAttribute("foo");
        $this->assertCount(0, $el->getAttributeNames());

        $el->setAttribute("foo", "bar");
        $el->setAttributeNS("", "FOO", "bar");
        $el->setAttributeNS("dummy1", "foo", "bar");
        $el->setAttributeNS("dummy2", "dummy:foo", "bar");
        $this->assertCount(4, $el->getAttributeNames());
        $this->assertSame("foo", $el->getAttributeNames()[0]);
        $this->assertSame("FOO", $el->getAttributeNames()[1]);
        $this->assertSame("foo", $el->getAttributeNames()[2]);
        $this->assertSame("dummy:foo", $el->getAttributeNames()[3]);
        $this->assertSame($el->attributes[0]->name, $el->getAttributeNames()[0]);
        $this->assertSame($el->attributes[1]->name, $el->getAttributeNames()[1]);
        $this->assertSame($el->attributes[2]->name, $el->getAttributeNames()[2]);
        $this->assertSame($el->attributes[3]->name, $el->getAttributeNames()[3]);

        $el->removeAttributeNS("", "FOO");
        $this->assertCount(3, $el->getAttributeNames());
        $this->assertSame("foo", $el->getAttributeNames()[0]);
        $this->assertSame("foo", $el->getAttributeNames()[1]);
        $this->assertSame("dummy:foo", $el->getAttributeNames()[2]);
        $this->assertSame($el->attributes[0]->name, $el->getAttributeNames()[0]);
        $this->assertSame($el->attributes[1]->name, $el->getAttributeNames()[1]);
        $this->assertSame($el->attributes[2]->name, $el->getAttributeNames()[2]);
    }

    // There are no intentions to support the legacy behavior of adding attribute names as
    // properties on the NamedNodeMap object, so there is no point in adding tests for that
    // feature.

    public function childElementForTest2Provider(): Generator
    {
        foreach (self::getWindow()->document->getElementById('test2')->children as $child) {
            yield [$child];
        }
    }

    public function xmlnsNameProvider(): array
    {
        return [
            ["xmlns"],
            ["xmlns:a"],
            ["xmlnsx"],
            ["xmlns0"],
        ];
    }

    public static function getDocumentName(): string
    {
        return 'attributes.html';
    }
}
