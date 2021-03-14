<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * HTMLDocument represents an HTML document.
 */
class HTMLDocument extends Document
{
    public function __construct()
    {
        parent::__construct();

        $this->contentType = 'text/html';
    }
}
