<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom;

use Rowbot\DOM\Document;
use Rowbot\DOM\HTMLDocument;

use function call_user_func;

trait DocumentGetter
{
    protected $htmlDocument;
    protected $xmlDocument;

    public function getHTMLDocument(callable $callback = null): HTMLDocument
    {
        if (!$this->htmlDocument) {
            $this->htmlDocument = (new HTMLDocument())
                ->implementation
                ->createHTMLDocument();

            if ($callback !== null) {
                call_user_func($callback, $this->htmlDocument);
            }
        }

        return $this->htmlDocument;
    }

    public function getXMLDocument(callable $callback = null): Document
    {
        if (!$this->xmlDocument) {
            $this->xmlDocument = new Document();

            if ($callback !== null) {
                call_user_func($callback, $this->htmlDocument);
            }
        }

        return $this->xmlDocument;
    }

    public function tearDown(): void
    {
        unset($this->htmlDocument);
        unset($this->xmlDocument);
    }
}
