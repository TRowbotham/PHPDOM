<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * A shared abstraction for character and comment tokens, which both have data.
 *
 * {@inheritDoc}
 */
abstract class CharacterDataToken implements Token
{
    /**
     * @var string
     */
    public $data;

    /**
     * Constructor.
     *
     * @param string $data
     *
     * @return void
     */
    public function __construct(string $data = '')
    {
        $this->data = $data;
    }
}
