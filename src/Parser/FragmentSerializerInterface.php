<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Node;

interface FragmentSerializerInterface
{
    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements
     */
    public const VOID_ELEMENTS = [
        'area'   => true,
        'base'   => true,
        'br'     => true,
        'col'    => true,
        'embed'  => true,
        'hr'     => true,
        'img'    => true,
        'input'  => true,
        'link'   => true,
        'meta'   => true,
        'param'  => true,
        'source' => true,
        'track'  => true,
        'wbr'    => true,
    ];

    public function serializeFragment(Node $node, bool $requireWellFormed): string;
}
