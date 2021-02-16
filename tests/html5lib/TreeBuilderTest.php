<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html5lib;

use DirectoryIterator;
use PHPUnit\Framework\TestCase;
use Rowbot\DOM\Attr;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\ParserFactory;
use RuntimeException;
use SplQueue;
use SplStack;
use Throwable;

use function array_map;
use function ctype_alpha;
use function explode;
use function fgets;
use function fopen;
use function fseek;
use function rtrim;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR as DS;
use const SEEK_CUR;

/**
 * Test files taken from https://github.com/html5lib/html5lib-tests/tree/master/tree-construction
 */
class TreeBuilderTest extends TestCase
{
    private const TEST_FILES_DIR = __DIR__ . DS . 'test_data' . DS . 'tree-construction';

    /**
     * @dataProvider input
     *
     * @param \Rowbot\DOM\HTMLDocument|\Rowbot\DOM\DocumentFragment $expected
     */
    public function testTreeBuilder(string $data, array $errors, $expected, Element $context = null): void
    {
        if ($context === null) {
            $document = ParserFactory::parseHTMLDocument($data);

            self::assertTrue($expected->isEqualNode($document));

            return;
        }

        $fragment = ParserFactory::parseFragment($data, $context);

        self::assertTrue($expected->isEqualNode($fragment));
    }

    public function input(): array
    {
        $tests = [];
        $i = 0;

        foreach (new DirectoryIterator(self::TEST_FILES_DIR) as $file) {
            if ($file->isDir() || $file->isDot() || $file->getFilename() === 'README.md') {
                continue;
            }

            $handle = fopen($file->getPathname(), 'r');
            $line = fgets($handle);

            while ($line !== false) {
                switch ($line) {
                    case "#data\n":
                        $tests[$i] = [
                            'data' => '',
                            'errors' => [],
                            'expected' => new HTMLDocument(),
                            'context' => null,
                            'enableScripting' => null,
                        ];
                        $prevLine = '';

                        while (($line = fgets($handle)) !== false) {
                            if ($line === "#errors\n") {
                                $tests[$i]['data'] .= rtrim($prevLine, "\n");

                                continue 3;
                            }

                            $tests[$i]['data'] .= $prevLine;
                            $prevLine = $line;
                        }

                        break;

                    case "#errors\n":
                        while (($line = fgets($handle)) !== false) {
                            if ($line === "#new-errors\n") {
                                continue;
                            }

                            if ($line[0] === '#') {
                                continue 3;
                            }

                            $tests[$i]['errors'][] = rtrim($line);
                        }

                        break;

                    case "#script-on\n":
                        $tests[$i]['enableScripting'] = true;

                        break;

                    case "#script-off\n":
                        $tests[$i]['enableScripting'] = false;

                        break;

                    case "#document-fragment\n":
                        $line = fgets($handle);
                        $parts = explode(' ', rtrim($line));
                        $namespace = Namespaces::HTML;

                        if (isset($parts[1])) {
                            if ($parts[0] === 'math') {
                                $namespace = Namespaces::MATHML;
                            } elseif ($parts[0] === 'svg') {
                                $namespace = Namespaces::SVG;
                            }
                        }

                        $localName = $parts[1] ?? $parts[0];
                        $tests[$i]['context'] = ElementFactory::create($tests[$i]['expected'], $localName, $namespace);
                        $tests[$i]['expected'] = $tests[$i]['expected']->createDocumentFragment();

                        break;

                    case "#document\n":
                        $this->parseDocumentSection($handle, $tests[$i]['expected']);

                        // We don't support scripting
                        if ($tests[$i]['enableScripting']) {
                            array_pop($tests);

                            break;
                        }

                        ++$i;

                        break;
                }

                $line = fgets($handle);
            }
        }

        return $tests;
    }

    /**
     * @param resource                                              $handle
     * @param \Rowbot\DOM\HTMLDocument|\Rowbot\DOM\DocumentFragment $root
     */
    public function parseDocumentSection($handle, $root): void
    {
        $lineQueue = new SplQueue();

        while (($line = fgets($handle)) !== false) {
            if ($line === "\n") {
                $nextLine = fgets($handle);

                if ($nextLine === '' || $nextLine === false) {
                    break;
                }

                fseek($handle, -strlen($nextLine), SEEK_CUR);

                if ($nextLine[0] === '#') {
                    break;
                }
            }

            $lineQueue->enqueue($line);
        }

        $document = $root->ownerDocument ?? $root;
        $stack = new SplStack();
        $stack->push([$root, 0]);

        while (!$lineQueue->isEmpty()) {
            $buffer = $lineQueue->dequeue();

            while (!$lineQueue->isEmpty()) {
                if ($lineQueue->bottom()[0] === '|') {
                    break;
                }

                $buffer .= $lineQueue->dequeue();
            }

            $indent = strspn($buffer, ' ', 1);

            while (!$stack->isEmpty()) {
                if ($indent > $stack->top()[1]) {
                    break;
                }

                $stack->pop();
            }

            if ($stack->isEmpty()) {
                throw new RuntimeException();
            }

            try {
                $parent = $stack->top()[0];
            } catch (Throwable $e) {
                throw new RuntimeException();
            }

            $buffer = substr($buffer, $indent + 1);

            if (strpos($buffer, '<!-- ') === 0) {
                // Comment
                $parent->appendChild($document->createComment(substr($buffer, 5, -5)));
            } elseif ($buffer[0] === '<' && ctype_alpha($buffer[1])) {
                // Element
                $parts = explode(' ', substr($buffer, 1, -2));
                $namespace = Namespaces::HTML;

                if (isset($parts[1])) {
                    if ($parts[0] === 'math') {
                        $namespace = Namespaces::MATHML;
                    } elseif ($parts[0] === 'svg') {
                        $namespace = Namespaces::SVG;
                    }
                }

                $localName = $parts[1] ?? $parts[0];
                $element = ElementFactory::create($document, $localName, $namespace);
                $parent->appendChild($element);
                $stack->push([$element, $indent]);
            } elseif (strpos($buffer, '<!DOCTYPE ') === 0) {
                // Doctype
                $parts = array_map('trim', explode(' ', substr($buffer, 10, -2), 2));
                $name = $parts[0] ?? '';
                $publicId = '';
                $systemId = '';

                if (isset($parts[1])) {
                    $nextQuote = strpos($parts[1], '"', 1);
                    $publicId = substr($parts[1], 1, $nextQuote - 1);

                    if (isset($parts[1][$nextQuote + 1])) {
                        $systemId = substr($parts[1], $nextQuote + 3, -1);
                    }
                }

                $parent->appendChild(new DocumentType($document, $name, $publicId, $systemId));
            } elseif (strpos($buffer, '<?') === 0) {
                // Processing instruction
            } elseif ($buffer[0] === '"') {
                // Plain text
                $text = substr($buffer, 1, -2);
                $parent->appendChild($document->createTextNode($text));
            } elseif ($buffer === "content\n") {
                // Template contents
                $stack->push([$parent->content, $indent]);
            } else {
                // Attribute
                [$name, $value] = explode('=', rtrim($buffer), 2);
                $parts = explode(' ', $name);
                $namespace = null;

                if (isset($parts[1])) {
                    $name = $parts[1];

                    if ($parts[0] === 'xlink') {
                        $namespace = Namespaces::XLINK;
                    } elseif ($parts[0] === 'xml') {
                        $namespace = Namespaces::XML;
                    } elseif ($parts[0] === 'xmlns') {
                        $namespace = Namespaces::XMLNS;
                    }
                }

                $value = substr($value, 1, -1);
                $parent->setAttributeNode(new Attr($document, $name, $value, $namespace));
            }
        }
    }
}
