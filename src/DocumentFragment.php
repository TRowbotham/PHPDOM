<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\TypeError;

use function count;

/**
 * @see https://dom.spec.whatwg.org/#interface-documentfragment
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DocumentFragment
 *
 * @property-read \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element> $children
 * @property-read \Rowbot\DOM\Element\Element|null                        $firstElementChild
 * @property-read \Rowbot\DOM\Element\Element|null                        $lastElementChild
 * @property-read int                                                     $childElementCount
 */
class DocumentFragment extends Node implements NonElementParentNode, ParentNode
{
    use NonElementParentNodeTrait;
    use ParentNodeTrait;

    public string $nodeName {
        get => '#document-fragment';
    }

    public ?string $textContent {
        get {
            $node = $this->nextNode($this);
            $data = '';

            while ($node) {
                if ($node instanceof Text && !$node instanceof CDATASection) {
                    $data .= $node->data;
                }

                $node = $node->nextNode($this);
            }

            return $data;
        }
        set(float|int|string|null $value) {
            if ($value === null) {
                $value = '';
            }

            if (!Utils::isStringable($value)) {
                throw new TypeError();
            }

            $node = null;

            if ($value !== '') {
                $node = new Text($this->nodeDocument, (string) $value);
            }

            $this->replaceAllNodes($node);
        }
    }

    protected ?Element $host;

    public function __construct(Document $document)
    {
        parent::__construct($document, self::DOCUMENT_FRAGMENT_NODE);

        $this->host = null;
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $this->hasEqualChildNodes($otherNode);
    }

    /**
     * Gets a DocumentFragment's host object.
     *
     * @internal
     */
    public function getHost(): ?Element
    {
        return $this->host;
    }

    /**
     * Sets a DocumentFragment's host element, if it has one.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-documentfragment-host
     *
     * @param \Rowbot\DOM\Element\Element|null $host The element that is hosting the DocumentFragment such as a template
     *                                         element or shadow root.
     */
    public function setHost(?Element $host): void
    {
        $this->host = $host;
    }

    public function getLength(): int
    {
        return count($this->childNodes_);
    }
}
