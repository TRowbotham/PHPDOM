<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html5lib;

use DirectoryIterator;
use Generator;
use PHPUnit\Framework\TestCase;
use Rowbot\DOM\Parser\Collection\OpenElementStack;
use Rowbot\DOM\Parser\HTML\ParserState;
use Rowbot\DOM\Parser\HTML\Tokenizer;
use Rowbot\DOM\Parser\HTML\TokenizerState;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Support\CodePointStream;
use Rowbot\Idna\CodePoint;
use RuntimeException;

use function count;
use function file_get_contents;
use function json_decode;
use function mb_check_encoding;
use function mb_convert_encoding;
use function preg_match;
use function preg_replace;

use const DIRECTORY_SEPARATOR as DS;
use const JSON_ERROR_NONE;

/**
 * Test files taken from https://github.com/html5lib/html5lib-tests/tree/master/tokenizer
 */
class TokenizerTest extends TestCase
{
    private const STATE_MAP = [
        'Data state'          => TokenizerState::DATA,
        'PLAINTEXT state'     => TokenizerState::PLAINTEXT,
        'RCDATA state'        => TokenizerState::RCDATA,
        'RAWTEXT state'       => TokenizerState::RAWTEXT,
        'Script data state'   => TokenizerState::SCRIPT_DATA,
        'CDATA section state' => TokenizerState::CDATA_SECTION,
    ];

    private const TOKEN_MAP = [
        'DOCTYPE'   => DoctypeToken::class,
        'StartTag'  => StartTagToken::class,
        'EndTag'    => EndTagToken::class,
        'Comment'   => CommentToken::class,
        'Character' => CharacterToken::class,
    ];

    private const TEST_FILES_DIR = __DIR__ . DS . 'test_data' . DS . 'tokenizer';

    /**
     * @dataProvider tokenizerTestProvider
     */
    public function testTokenizer(string $description, string $input, array $output, int $state, ?string $lastStartTag): void
    {
        $stream = new CodePointStream();
        $this->preprocessInput($input, $stream);

        $parserState = new ParserState();
        $parserState->tokenizerState = $state;
        $tokenizer = new Tokenizer($stream, new OpenElementStack(), false, null, $parserState);

        if ($lastStartTag !== null) {
            $tokenizer->setLastEmittedStartTagToken(new StartTagToken($lastStartTag));
        }

        $gen = $tokenizer->run();
        $length = count($output);
        $tokensSeen = 0;

        for ($i = 0; $i < $length; ++$i) {
            $expectedToken = $output[$i];
            self::assertTrue($gen->valid());
            ++$tokensSeen;
            $token = $gen->current();
            self::assertInstanceOf(self::TOKEN_MAP[$expectedToken[0]], $token);

            switch ($expectedToken[0]) {
                case 'DOCTYPE':
                    self::assertSame($expectedToken[1], $token->name);
                    self::assertSame($expectedToken[2], $token->publicIdentifier);
                    self::assertSame($expectedToken[3], $token->systemIdentifier);
                    $correctness = $expectedToken[4] ? 'off' : 'on';
                    self::assertSame($correctness, $token->getQuirksMode());

                    break;

                case 'StartTag':
                    self::assertSame($expectedToken[1], $token->tagName);
                    self::assertSame(count($expectedToken[2]), count($token->attributes));
                    $j = 0;

                    foreach ($expectedToken[2] as $attrName => $attrValue) {
                        self::assertSame((string) $attrName, $token->attributes[$j]->name);
                        self::assertSame((string) $attrValue, $token->attributes[$j]->value);
                        ++$j;
                    }

                    if (isset($expectedToken[3])) {
                        self::assertTrue($token->isSelfClosing());
                    }

                    break;

                case 'EndTag':
                    self::assertSame($expectedToken[1], $token->tagName);

                    break;

                case 'Comment':
                    self::assertSame($expectedToken[1], $token->data);

                    break;

                case 'Character':
                    $data = $token->data;
                    $gen->next();

                    while ($gen->valid()) {
                        $token = $gen->current();

                        if (!$token instanceof CharacterToken) {
                            self::assertSame($expectedToken[1], $data);

                            continue 3;
                        }

                        $data .= $token->data;
                        $gen->next();
                    }

                    self::assertSame($expectedToken[1], $data);

                    break;
            }

            $gen->next();
        }

        self::assertSame($length, $tokensSeen);
        self::assertInstanceOf(EOFToken::class, $gen->current());
    }

    public function tokenizerTestProvider(): Generator
    {
        foreach (new DirectoryIterator(self::TEST_FILES_DIR) as $file) {
            if ($file->isDir() || $file->isDot() || $file->getFilename() === 'README.md') {
                continue;
            }

            $json = json_decode(file_get_contents($file->getPathname()), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException();
            }

            $key = $file->getFilename() === 'xmlViolation.test'
                ? 'xmlViolationTests'
                : 'tests';

            foreach ($json[$key] as $test) {
                if (isset($test['doubleEscaped'])) {
                    $test['input'] = preg_replace_callback(
                        '/\\\u([[:xdigit:]]{4})/',
                        static function (array $matches) {
                            return CodePoint::encode(intval($matches[1], 16));
                        },
                        $test['input']
                    );

                    for ($i = 0, $length = count($test['output']); $i < $length; ++$i) {
                        $test['output'][$i][1] = preg_replace_callback(
                            '/\\\u([[:xdigit:]]{4})/',
                            static function (array $matches) {
                                return CodePoint::encode(intval($matches[1], 16));
                            },
                            $test['output'][$i][1]
                        );
                    }
                }

                $a = [$test['description'], $test['input'], $test['output'], TokenizerState::DATA, $test['lastStartTag'] ?? null];

                if (!isset($test['initialStates'])) {
                    yield $a;

                    continue;
                }

                foreach ($test['initialStates'] as $initialState) {
                    $a[3] = self::STATE_MAP[$initialState];

                    yield $a;
                }
            }
        }
    }

    public function preprocessInput(string $input, CodePointStream $stream): void
    {
        $orig = mb_substitute_character();
        mb_substitute_character(0xFFFD);
        $input = mb_convert_encoding($input, 'utf-8', 'utf-8');
        mb_substitute_character($orig);

        if (
            preg_match(
                '/[\x01-\x08\x0E-\x1F\x7F-\x9F\x{FDD0}-\x{FDEF}\x0B'
                . '\x{FFFE}\x{FFFF}'
                . '\x{1FFFE}\x{1FFFF}'
                . '\x{2FFFE}\x{2FFFF}'
                . '\x{3FFFE}\x{3FFFF}'
                . '\x{4FFFE}\x{4FFFF}'
                . '\x{5FFFE}\x{5FFFF}'
                . '\x{6FFFE}\x{6FFFF}'
                . '\x{7FFFE}\x{7FFFF}'
                . '\x{8FFFE}\x{8FFFF}'
                . '\x{9FFFE}\x{9FFFF}'
                . '\x{AFFFE}\x{AFFFF}'
                . '\x{BFFFE}\x{BFFFF}'
                . '\x{CFFFE}\x{CFFFF}'
                . '\x{DFFFE}\x{DFFFF}'
                . '\x{EFFFE}\x{EFFFF}'
                . '\x{FFFFE}\x{FFFFF}'
                . '\x{10FFFE}\x{10FFFF}]/u',
                $input
            )
        ) {
            // Parse error
        }

        // Any character that is a not a Unicode character, i.e. any isolated
        // surrogate, is a parse error. (These can only find their way into the
        // input stream via script APIs such as document.write().)
        if (!mb_check_encoding($input, 'utf-8')) {
            // Parse error
        }

        // U+000D CARRIAGE RETURN (CR) characters and U+000A LINE FEED (LF)
        // characters are treated specially. Any LF character that immediately
        // follows a CR character must be ignored, and all CR characters must
        // then be converted to LF characters. Thus, newlines in HTML DOMs are
        // represented by LF characters, and there are never any CR characters
        // in the input to the tokenization stage.
        $stream->append(preg_replace(['/\x0D\x0A/u', '/\x0D/u'], "\x0A", $input));
    }
}
