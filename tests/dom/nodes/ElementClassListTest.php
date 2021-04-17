<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use TypeError;

use function array_fill;
use function array_push;
use function count;
use function in_array;
use function is_array;
use function is_string;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-classlist.html
 */
class ElementClassListTest extends TestCase
{
    use DocumentGetter;

    public function setClass(Element $e, $newVal): void
    {
        if ($newVal === null) {
            $e->removeAttribute('class');
        } else {
            $e->setAttribute('class', $newVal);
        }
    }

    public function checkModification(
        Element $e,
        string $funcName,
        $args,
        $expectedRes,
        $before,
        $after,
        $expectedException
    ): void {
        if (!is_array($args)) {
            $args = [$args];
        }

        $shouldThrow = is_string($expectedException);

        if ($shouldThrow) {
            // If an exception is thrown, the class attribute shouldn't change.
            $after = $before;
        }

        $this->setClass($e, $before);

        if (in_array(null, $args, true)) {
            $this->expectException(TypeError::class);
        }

        if ($shouldThrow) {
            $list = $e->classList;

            $this->assertThrows(static function () use ($list, $funcName, $args, &$res) {
                $res = $list->{$funcName}(...$args);
            }, $expectedException);
        } else {
            $res = $e->classList->{$funcName}(...$args);
        }

        if (!$shouldThrow) {
            $this->assertSame($expectedRes, $res, "wrong return value");
        }

        $expectedAfter = $after;
        $this->assertSame(
            $expectedAfter,
            $e->getAttribute("class"),
            "wrong class after modification"
        );
    }

    public function buildTestData(array $data)
    {
        $testData = [];
        $length = count($data);

        for ($i = 0; $i < $length; ++$i) {
            foreach ($this->nodeProvider() as $node) {
                $arr = [$node];
                array_push($arr, ...$data[$i]);
                $testData[] = $arr;
            }
        }

        return $testData;
    }

    /**
     * @return array<\Rowbot\DOM\Element\Element>
     */
    public function nodeProvider(): array
    {
        $document = $this->getHTMLDocument();

        return [
            $document->createElement("div"),
            $document->createElementNS(Namespaces::HTML, "div"),
            $document->createElementNS(Namespaces::MATHML, "math"),
            $document->createElementNS(null, "foo"),
            $document->createElementNS("http://example.org/foo", "foo"),
        ];
    }

    /**
     * @return array {
     *      @var \Rowbot\DOM\Element\Element $element
     *      @var string|null                 $value
     *      @var int                         $length
     * }
     */
    public function lengthProvider(): array
    {
        return $this->buildTestData([
            [null, 0],
            ["", 0],
            ["   \t  \f", 0],
            ["a", 1],
            ["a A", 2],
            ["\r\na\t\f", 1],
            ["a a", 1],
            ["a a a a a a", 1],
            ["a a b b", 2],
            ["a A B b", 4],
            ["a b c c b a a b c c", 3],
            ["   a  a b", 2],
            ["a\tb\nc\fd\re f", 6],
        ]);
    }

    /**
     * @dataProvider lengthProvider
     */
    public function testLength(Element $element, ?string $value, int $length)
    {
        $this->setClass($element, $value);
        $this->assertSame($length, $element->classList->length);
    }

    /**
     * @return array {
     *      @var \Rowbot\DOM\Element\Element $element
     *      @var string                      $value
     *      @var string                      $expected
     * }
     */
    public function stringifierProvider()
    {
        return $this->buildTestData([
            [null, ""],
            ["foo", "foo"],
            ["   a  a b", "   a  a b"],
        ]);
    }

    /**
     * @dataProvider stringifierProvider
     *
     * @param \Rowbot\DOM\Element\Element $element
     * @param ?string                     $value
     * @param string                      $expected
     *
     * @return void
     */
    public function testStringifier(
        Element $element,
        ?string $value,
        string $expected
    ) {
        $this->setClass($element, $value);
        $this->assertSame($expected, $element->classList->toString());
    }

    public function itemsProvider()
    {
        return $this->buildTestData([
            [null, []],
            ["a", ["a"]],
            ["aa AA aa", ["aa", "AA"]],
            ["a b", ["a", "b"]],
            ["   a  a b", ["a", "b"]],
            ["\t\n\f\r a\t\n\f\r b\t\n\f\r ", ["a", "b"]],
        ]);
    }

    /**
     * @dataProvider itemsProvider
     *
     * @param string   $attributeValue
     * @param string[] $expectedValues
     *
     * @return void
     */
    public function testCheckItems(
        Element $element,
        ?string $attributeValue,
        array $expectedValues
    ): void {
        $this->setClass($element, $attributeValue);
        $this->assertNull($element->classList->item(-1));
        $this->assertNull($element->classList[-1]);

        for ($i = 0; $i < count($expectedValues); $i++) {
            $this->assertSame($expectedValues[$i], $element->classList->item($i));
            $this->assertSame($expectedValues[$i], $element->classList[$i]);
        }

        $this->assertNull($element->classList->item($i));
        $this->assertNull($element->classList[$i]);

        $this->assertNull($element->classList->item(0xffffffff));
        $this->assertNull($element->classList[0xffffffff]);

        $this->assertNull($element->classList->item(0xfffffffe));
        $this->assertNull($element->classList[0xfffffffe]);
    }

    public function containsProvider()
    {
        return $this->buildTestData([
            [null, ["a", "", "  "], false],
            ["", ["a"], false],

            ["a", ["a"], true],
            ["a", ["aa", "b", "A", "a.", "a)", "a'", 'a"', "a$", "a~",
                                "a?", "a\\"], false],

            // All "ASCII whitespace" per spec, before and after
            ["a", ["a\t", "\ta", "a\n", "\na", "a\f", "\fa", "a\r", "\ra",
                                "a ", " a"], false],

            ["aa AA", ["aa", "AA", "aA"], [true, true, false]],
            ["a a a", ["a", "aa", "b"], [true, false, false]],
            ["a b c", ["a", "b"], true],

            ["\t\n\f\r a\t\n\f\r b\t\n\f\r ", ["a", "b"], true],
        ]);
    }

    /**
     * @dataProvider containsProvider
     *
     * @return void
     */
    public function testContains(
        Element $element,
        $attributeValue,
        $args,
        $expectedRes
    ): void {
        if (!is_array($expectedRes)) {
            $expectedRes = array_fill(0, count($args), $expectedRes);
        }

        $this->setClass($element, $attributeValue);

        for ($i = 0; $i < count($args); ++$i) {
            $this->assertSame(
                $expectedRes[$i],
                $element->classList->contains($args[$i])
            );
        }
    }

    public function addProvider()
    {
        return $this->buildTestData([
            [null, "", null, SyntaxError::class],
            [null, ["a", ""], null, SyntaxError::class],
            [null, " ", null, InvalidCharacterError::class],
            [null, "\ta", null, InvalidCharacterError::class],
            [null, "a\t", null, InvalidCharacterError::class],
            [null, "\na", null, InvalidCharacterError::class],
            [null, "a\n", null, InvalidCharacterError::class],
            [null, "\fa", null, InvalidCharacterError::class],
            [null, "a\f", null, InvalidCharacterError::class],
            [null, "\ra", null, InvalidCharacterError::class],
            [null, "a\r", null, InvalidCharacterError::class],
            [null, " a", null, InvalidCharacterError::class],
            [null, "a ", null, InvalidCharacterError::class],
            [null, ["a", " "], null, InvalidCharacterError::class],
            [null, ["a", "aa "], null, InvalidCharacterError::class],

            ["a", "a", "a"],
            ["aa", "AA", "aa AA"],
            ["a b c", "a", "a b c"],
            ["a a a  b", "a", "a b", "noop"],
            [null, "a", "a"],
            ["", "a", "a"],
            [" ", "a", "a"],
            ["   \f", "a", "a"],
            ["a", "b", "a b"],
            ["a b c", "d", "a b c d"],
            ["a b c ", "d", "a b c d"],
            ["   a  a b", "c", "a b c"],
            ["   a  a b", "a", "a b", "noop"],
            ["\t\n\f\r a\t\n\f\r b\t\n\f\r ", "c", "a b c"],

            // multiple add
            ["a b c ", ["d", "e"], "a b c d e"],
            ["a b c ", ["a", "a"], "a b c"],
            ["a b c ", ["d", "d"], "a b c d"],
            ["a b c a ", [], "a b c"],
            [null, ["a", "b"], "a b"],
            ["", ["a", "b"], "a b"],

            [null, null, "null"],
        ]);
    }

    /**
     * @dataProvider addProvider
     *
     * @param \Rowbot\DOM\Element\Element $element
     * @param string|null $before
     * @param [type] $argument
     * @param string|null $after
     * @param string|null $param
     *
     * @return void
     */
    public function testAdd(
        Element $element,
        ?string $before,
        $argument,
        ?string $after,
        ?string $param = null
    ): void {
        $expectedException = null;
        $noop = false;

        if ($param === "noop") {
            $noop = true;
        } else {
            $expectedException = $param;
        }

        $this->checkModification(
            $element,
            "add",
            $argument,
            null,
            $before,
            $after,
            $expectedException
        );

        if (!is_array($argument)) {
            $this->checkModification(
                $element,
                "toggle",
                [$argument, true],
                true,
                $before,
                $noop ? $before : $after,
                $expectedException
            );
        }
    }

    /**
     * @return array
     */
    public function removeProvider(): array
    {
        return $this->buildTestData([
            [null, "", null, SyntaxError::class],
            [null, " ", null, InvalidCharacterError::class],
            ["\ta", "\ta", "\ta", InvalidCharacterError::class],
            ["a\t", "a\t", "a\t", InvalidCharacterError::class],
            ["\na", "\na", "\na", InvalidCharacterError::class],
            ["a\n", "a\n", "a\n", InvalidCharacterError::class],
            ["\fa", "\fa", "\fa", InvalidCharacterError::class],
            ["a\f", "a\f", "a\f", InvalidCharacterError::class],
            ["\ra", "\ra", "\ra", InvalidCharacterError::class],
            ["a\r", "a\r", "a\r", InvalidCharacterError::class],
            [" a", " a", " a", InvalidCharacterError::class],
            ["a ", "a ", "a ", InvalidCharacterError::class],
            ["aa ", "aa ", null, InvalidCharacterError::class],

            [null, "a", null],
            ["", "a", ""],
            ["a b  c", "d", "a b c", "noop"],
            ["a b  c", "A", "a b c", "noop"],
            [" a a a ", "a", ""],
            ["a  b", "a", "b"],
            ["a  b  ", "a", "b"],
            ["a a b", "a", "b"],
            ["aa aa bb", "aa", "bb"],
            ["a a b a a c a a", "a", "b c"],

            ["a  b  c", "b", "a c"],
            ["aaa  bbb  ccc", "bbb", "aaa ccc"],
            [" a  b  c ", "b", "a c"],
            ["a b b b c", "b", "a c"],

            ["a  b  c", "c", "a b"],
            [" a  b  c ", "c", "a b"],
            ["a b c c c", "c", "a b"],

            ["a b a c a d a", "a", "b c d"],
            ["AA BB aa CC AA dd aa", "AA", "BB aa CC dd"],

            ["\ra\na\ta\f", "a", ""],
            ["\t\n\f\r a\t\n\f\r b\t\n\f\r ", "a", "b"],

            // multiple remove
            ["a b c ", ["d", "e"], "a b c"],
            ["a b c ", ["a", "b"], "c"],
            ["a b c ", ["a", "c"], "b"],
            ["a b c ", ["a", "a"], "b c"],
            ["a b c ", ["d", "d"], "a b c"],
            ["a b c ", [], "a b c"],
            [null, ["a", "b"], null],
            ["", ["a", "b"], ""],
            ["a a", [], "a"],

            ["null", null, ""],
        ]);
    }

    /**
     * @dataProvider removeProvider
     *
     * @param \Rowbot\DOM\Element\Element $element
     * @param string|null $before
     * @param [type] $argument
     * @param string|null $after
     * @param string|null $param
     *
     * @return void
     */
    public function testRemove(
        Element $element,
        ?string $before,
        $argument,
        ?string $after,
        ?string $param = null
    ): void {
        $expectedException = null;
        $noop = false;

        if ($param === "noop") {
            $noop = true;
        } else {
            $expectedException = $param;
        }

        $this->checkModification(
            $element,
            "remove",
            $argument,
            null,
            $before,
            $after,
            $expectedException
        );

        if (!is_array($argument)) {
            $this->checkModification(
                $element,
                "toggle",
                [$argument, false],
                false,
                $before,
                $noop ? $before : $after,
                $expectedException
            );
        }
    }

    public function toggleProvider()
    {
        return $this->buildTestData([
            [null, "", null, null, SyntaxError::class],
            [null, "aa ", null, null, InvalidCharacterError::class],

            [null, "a", true, "a"],
            ["", "a", true, "a"],
            [" ", "a", true, "a"],
            ["   \f", "a", true, "a"],
            ["a", "b", true, "a b"],
            ["a", "A", true, "a A"],
            ["a b c", "d", true, "a b c d"],
            ["   a  a b", "d", true, "a b d"],

            ["a", "a", false, ""],
            [" a a a ", "a", false, ""],
            [" A A A ", "a", true, "A a"],
            [" a b c ", "b", false, "a c"],
            [" a b c b b", "b", false, "a c"],
            [" a b  c  ", "c", false, "a b"],
            [" a b c ", "a", false, "b c"],
            ["   a  a b", "b", false, "a"],
            ["\t\n\f\r a\t\n\f\r b\t\n\f\r ", "a", false, "b"],
            ["\t\n\f\r a\t\n\f\r b\t\n\f\r ", "c", true, "a b c"],

            ["null", null, false, ""],
            ["", null, true, "null"],
        ]);
    }

    /**
     * @dataProvider toggleProvider
     *
     * @param \Rowbot\DOM\Element\Element $element
     * @param ?string                     $before
     * @param ?string                     $argument
     * @param ?bool                       $expectedRes
     * @param ?string                     $after
     * @param ?string                     $expectedException
     */
    public function testToggleHTMLElement(
        Element $element,
        ?string $before,
        ?string $argument,
        ?bool $expectedRes,
        ?string $after,
        ?string $expectedException = null
    ): void {
        $this->checkModification(
            $element,
            "toggle",
            $argument,
            $expectedRes,
            $before,
            $after,
            $expectedException
        );
    }

    public function replaceProvider()
    {
        return $this->buildTestData([
            [null, "", "a", null, null, SyntaxError::class],
            [null, "", " ", null, null, SyntaxError::class],
            [null, " ", "a", null, null, InvalidCharacterError::class],
            [null, "\ta", "b", null, null, InvalidCharacterError::class],
            [null, "a\t", "b", null, null, InvalidCharacterError::class],
            [null, "\na", "b", null, null, InvalidCharacterError::class],
            [null, "a\n", "b", null, null, InvalidCharacterError::class],
            [null, "\fa", "b", null, null, InvalidCharacterError::class],
            [null, "a\f", "b", null, null, InvalidCharacterError::class],
            [null, "\ra", "b", null, null, InvalidCharacterError::class],
            [null, "a\r", "b", null, null, InvalidCharacterError::class],
            [null, " a", "b", null, null, InvalidCharacterError::class],
            [null, "a ", "b", null, null, InvalidCharacterError::class],
            [null, "a", "", null, null, SyntaxError::class],
            [null, " ", "", null, null, SyntaxError::class],
            [null, "a", " ", null, null, InvalidCharacterError::class],
            [null, "b", "\ta", null, null, InvalidCharacterError::class],
            [null, "b", "a\t", null, null, InvalidCharacterError::class],
            [null, "b", "\na", null, null, InvalidCharacterError::class],
            [null, "b", "a\n", null, null, InvalidCharacterError::class],
            [null, "b", "\fa", null, null, InvalidCharacterError::class],
            [null, "b", "a\f", null, null, InvalidCharacterError::class],
            [null, "b", "\ra", null, null, InvalidCharacterError::class],
            [null, "b", "a\r", null, null, InvalidCharacterError::class],
            [null, "b", " a", null, null, InvalidCharacterError::class],
            [null, "b", "a ", null, null, InvalidCharacterError::class],

            ["a", "a", "a", true, "a"],
            ["a", "a", "b", true, "b"],
            ["a", "A", "b", false, "a"],
            ["a b", "b", "A", true, "a A"],
            ["a b", "c", "a", false, "a b"],
            ["a b c", "d", "e", false, "a b c"],
            // https://github.com/whatwg/dom/issues/443
            ["a a a  b", "a", "a", true, "a b"],
            ["a a a  b", "c", "d", false, "a a a  b"],
            [null, "a", "b", false, null],
            ["", "a", "b", false, ""],
            [" ", "a", "b", false, " "],
            [" a  \f", "a", "b", true, "b"],
            ["a b c", "b", "d", true, "a d c"],
            ["a b c", "c", "a", true, "a b"],
            ["c b a", "c", "a", true, "a b"],
            ["a b a", "a", "c", true, "c b"],
            ["a b a", "b", "c", true, "a c"],
            ["   a  a b", "a", "c", true, "c b"],
            ["   a  a b", "b", "c", true, "a c"],
            ["\t\n\f\r a\t\n\f\r b\t\n\f\r ", "a", "c", true, "c b"],
            ["\t\n\f\r a\t\n\f\r b\t\n\f\r ", "b", "c", true, "a c"],

            ["a null", null, "b", true, "a b"],
            ["a b", "a", null, true, "null b"],
        ]);
    }

    /**
     * @dataProvider replaceProvider
     *
     * @param \Rowbot\DOM\Element\Element $element
     * @param ?string                     $before
     * @param ?string                      $token
     * @param ?string                      $newToken
     * @param ?bool                       $expectedRes
     * @param ?string                     $after
     * @param ?string                     $expectedException
     */
    public function testReplaceHTMLElement(
        Element $element,
        ?string $before,
        ?string $token,
        ?string $newToken,
        ?bool $expectedRes,
        ?string $after,
        ?string $expectedException = null
    ): void {
        $this->checkModification(
            $element,
            "replace",
            [$token, $newToken],
            $expectedRes,
            $before,
            $after,
            $expectedException
        );
    }
}
