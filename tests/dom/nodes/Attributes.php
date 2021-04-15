<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Element\Element;

use function count;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/attributes.js
 */
trait Attributes
{
    public function attr_is(Attr $attr, $v, $ln, $ns, $p, $n)
    {
        $this->assertSame($v, $attr->value);
        $this->assertSame($v, $attr->nodeValue);
        $this->assertSame($v, $attr->textContent);
        $this->assertSame($ln, $attr->localName);
        $this->assertSame($ns, $attr->namespaceURI);
        $this->assertSame($p, $attr->prefix);
        $this->assertSame($n, $attr->name);
        $this->assertSame($n, $attr->nodeName);
        // TODO: We don't support Attr::$specified.
        //$this->assertTrue($attr->specified);
    }

    public function attributes_are(Element $el, array $l)
    {
        for ($i = 0, $il = count($l); $i < $il; $i++) {
            $this->attr_is(
                $el->attributes[$i],
                $l[$i][1],
                $l[$i][0],
                count($l[$i]) < 3 ? null : $l[$i][2],
                null,
                $l[$i][0]
            );
            $this->assertSame($el, $el->attributes[$i]->ownerElement);
        }
    }
}
