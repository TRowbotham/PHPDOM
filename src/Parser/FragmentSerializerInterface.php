<?php
namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Node;

interface FragmentSerializerInterface
{
    public const VOID_TAGS = '/^(area|base|basefont|bgsound|br|col|embed|frame'
        . '|hr|img|input|keygen|link|menuitem|meta|param|source|track|wbr)$/';

    public function serializeFragment(
        Node $node,
        bool $requireWellFormed
    ): string;
}
