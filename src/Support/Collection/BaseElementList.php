<?php

declare(strict_types=1);

namespace Rowbot\DOM\Support\Collection;

use Rowbot\DOM\Document;
use Rowbot\DOM\Element\HTML\HTMLBaseElement;

use function array_search;
use function array_splice;
use function assert;
use function ceil;
use function count;
use function in_array;

/**
 * @internal
 */
class BaseElementList
{
    /**
     * @var \Rowbot\DOM\Element\HTML\HTMLBaseElement|null
     */
    private $active;

    /**
     * @var list<\Rowbot\DOM\Element\HTML\HTMLBaseElement>
     */
    private $list;

    public function __construct()
    {
        $this->active = null;
        $this->list = [];
    }

    public function add(HTMLBaseElement $base): bool
    {
        if ($this->list !== []) {
            $l = 0;
            $r = count($this->list) - 1;

            while ($l !== $r) {
                $m = (int) ceil(($l + $r) / 2);

                if ($this->list[$m]->compareDocumentPosition($base) & HTMLBaseElement::DOCUMENT_POSITION_FOLLOWING) {
                    $l = $m;
                } else {
                    $r = $m - 1;
                }
            }

            array_splice($this->list, $l, 0, [$base]);
        } else {
            $this->list[] = $base;
        }

        // If the base element isn't actually in the tree, (e.g. it was inserted into a DocumentFragment), then
        // we don't want to do anything with it.
        if (!$base->getRootNode() instanceof Document) {
            return false;
        }

        // If the base element doesn't have an "href" attribute, then it has no effect on the document and we don't
        // support the "target" attribute for browsing contexts.
        if ($base->getAttributeList()->getAttrByNamespaceAndLocalName(null, 'href') === null) {
            return false;
        }

        $shouldActivate = $this->active === null
            || $this->active->compareDocumentPosition($base) & HTMLBaseElement::DOCUMENT_POSITION_FOLLOWING;

        if ($shouldActivate) {
            $this->active = $base;
        }

        return $shouldActivate;
    }

    public function remove(HTMLBaseElement $base): bool
    {
        $index = array_search($base, $this->list, true);
        assert($index !== false);
        array_splice($this->list, $index, 1);

        if ($this->list === []) {
            $this->active = null;

            return false;
        }

        // If the base element isn't actually in the tree, then no activation change will happen.
        if (!$base->getRootNode() instanceof Document) {
            return false;
        }

        for ($i = $index - 1; $i > -1; --$i) {
            if ($this->list[$i]->getAttributeList()->getAttrByNamespaceAndLocalName(null, 'href') !== null) {
                $this->active = $this->list[$i];

                return true;
            }
        }

        // No other suitable base element was found.
        $this->active = null;

        return false;
    }

    public function getActiveBase(): ?HTMLBaseElement
    {
        return $this->active;
    }

    public function setActiveBase(HTMLBaseElement $base): void
    {
        assert(in_array($base, $this->list, true));
        $this->active = $base;
    }
}
