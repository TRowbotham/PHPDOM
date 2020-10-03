<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\dom\Window;

use function file_get_contents;

class FakeIframe
{
    public $contentWindow;
    public $contentDocument;

    protected function __construct(Document $document)
    {
        $this->contentWindow = new class ($document) extends Window {
            public $unexpectedException;
            public $testNode;
            public $testNodeInput;
            public $testRangeInput;
            public $testRange;

            public function __construct(Document $document)
            {
                parent::__construct($document);

                $this->unexpectedException = null;
            }

            public function run()
            {
                $this->unexpectedException = null;

                if ($this->testNodeInput) {
                    $this->testNode = $this->eval($this->testNodeInput);
                }

                try {
                    $rangeEndpoints = $this->eval($this->testRangeInput);

                    if ($rangeEndpoints === "detached") {
                        $range = $this->document->createRange();
                        $range->detach();
                    } else {
                        $range = self::ownerDocument($rangeEndpoints[0])->createRange();
                        $range->setStart($rangeEndpoints[0], $rangeEndpoints[1]);
                        $range->setEnd($rangeEndpoints[2], $rangeEndpoints[3]);
                    }

                    $this->testRange = $range;
                } catch (Exception $e) {
                    $this->unexpectedException = $e;
                }
            }
        };
        $this->contentDocument = $document;
    }

    public static function load(string $file): self
    {
        $parser = new DOMParser();
        $document = $parser->parseFromString(file_get_contents($file), 'text/html');

        return new self($document);
    }
}
