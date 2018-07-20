<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * A DOCTYPE token has a name, a public identifier, a system identifier, and a force-quirks flag. When a DOCTYPE token
 * is created, its name, public identifier, and system identifier must be marked as missing (which is a distinct state
 * from the empty string), and the force-quirks flag must be set to off (its other state is on).
 *
 * {@inheritDoc}
 */
class DoctypeToken implements Token
{
    /**
     * @var string|null
     */
    public $publicIdentifier;

    /**
     * @var string|null
     */
    public $name;

    /**
     * @var string
     */
    private $forceQuirksMode;

    /**
     * @var string|null
     */
    public $systemIdentifier;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->forceQuirksMode = 'off';
    }

    /**
     * Gets the value of the quirks-flag.
     *
     * @return string
     */
    public function getQuirksMode(): string
    {
        return $this->forceQuirksMode;
    }

    /**
     * Sets the quirks-flag.
     *
     * @param string $mode
     */
    public function setQuirksMode(string $mode): void
    {
        $this->forceQuirksMode = $mode;
    }
}
