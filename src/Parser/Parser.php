<?php
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
     *
     * @return void
     */
    abstract public function abort(): void;

    /**
     * Preprocesses the input stream.
     *
     * @param string $input
     *
     * @return void
     */
    abstract public function preprocessInputStream(string $input): void;
}
