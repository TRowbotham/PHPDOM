<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Support\CodePointStream;

abstract class Parser
{
    /**
     * @var \Rowbot\DOM\Support\CodePointStream
     */
    protected $inputStream;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->inputStream = new CodePointStream();
    }

    /**
     * Runs steps for aborting the parsing steps.
     */
    abstract public function abort(): void;

    /**
     * Preprocesses the input stream.
     */
    abstract public function preprocessInputStream(string $input): void;
}
