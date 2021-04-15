<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/creators.js
 */
trait Creators
{
    public $creators = [
        'element' => 'createElement',
        'text'    => 'createTextNode',
        'comment' => 'createComment',
    ];
}
