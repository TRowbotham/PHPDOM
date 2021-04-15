<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-intersectsNode-2.html
 */
class RangeIntersectsNode2Test extends RangeTestCase
{
    use WindowTrait;

    public function testIntersectsNode(): void
    {
        $document = self::getWindow()->document;
        $range = new Range($document);
        $div = $document->getElementById('div');
        $s0 = $document->getElementById('s0');
        $s1 = $document->getElementById('s1');
        $s2 = $document->getElementById('s2');

        // Range encloses s0
        $range->setStart($div, 0);
        $range->setEnd($div, 1);

        $this->assertTrue($range->intersectsNode($s0));
        $this->assertFalse($range->intersectsNode($s1));
        $this->assertFalse($range->intersectsNode($s2));

        // Range encloses s1
        $range->setStart($div, 1);
        $range->setEnd($div, 2);

        $this->assertFalse($range->intersectsNode($s0));
        $this->assertTrue($range->intersectsNode($s1));
        $this->assertFalse($range->intersectsNode($s2));

        // Range encloses s2
        $range->setStart($div, 2);
        $range->setEnd($div, 3);

        $this->assertFalse($range->intersectsNode($s0));
        $this->assertFalse($range->intersectsNode($s1));
        $this->assertTrue($range->intersectsNode($s2));
    }

    public static function getDocumentName(): string
    {
        return 'Range-intersectsNode-2.html';
    }
}
