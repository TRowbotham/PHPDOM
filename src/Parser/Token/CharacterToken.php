<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * Character tokens have data.
 *
 * {@inheritDoc}
 */
class CharacterToken implements Token
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
    public function __construct(string $data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        }
    }
}
