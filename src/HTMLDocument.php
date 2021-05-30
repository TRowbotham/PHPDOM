<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * HTMLDocument represents an HTML document.
 */
class HTMLDocument extends Document
{
    public function __construct(?Environment $env = null)
    {
        $env = $env ?? new Environment(null, 'text/html');
        $env->setContentType('text/html');

        parent::__construct($env, 'html');
    }
}
