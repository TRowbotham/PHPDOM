<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Collection;

use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;
use Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException;

use function array_push;
use function array_search;
use function array_splice;
use function assert;
use function spl_object_id;

/**
 * @extends \Rowbot\DOM\Parser\Collection\ObjectStack<\Rowbot\DOM\Element\Element>
 */
class OpenElementStack extends ObjectStack
{
    private const SPECIFIC_SCOPE_HTML = [
        'applet'   => false,
        'caption'  => false,
        'html'     => false,
        'table'    => false,
        'td'       => false,
        'th'       => false,
        'marquee'  => false,
        'object'   => false,
        'template' => false,
    ];

    private const SPECIFIC_SCOPE_MATHML = [
        'mi'             => false,
        'mo'             => false,
        'mn'             => false,
        'ms'             => false,
        'mtext'          => false,
        'annotation-xml' => false,
    ];

    private const SPECIFIC_SCOPE_SVG = [
        'foreignObject' => false,
        'desc'          => false,
        'title'         => false,
    ];

    private const SPECIFIC_SCOPE = [
        Namespaces::HTML   => self::SPECIFIC_SCOPE_HTML,
        Namespaces::MATHML => self::SPECIFIC_SCOPE_MATHML,
        Namespaces::SVG    => self::SPECIFIC_SCOPE_SVG,
    ];

    private const LIST_ITEM_SCOPE = [
        Namespaces::HTML   => self::SPECIFIC_SCOPE_HTML + ['ol' => false, 'ul' => false],
        Namespaces::MATHML => self::SPECIFIC_SCOPE_MATHML,
        Namespaces::SVG    => self::SPECIFIC_SCOPE_SVG,
    ];

    private const BUTTON_SCOPE = [
        Namespaces::HTML   => self::SPECIFIC_SCOPE_HTML + ['button' => false],
        Namespaces::MATHML => self::SPECIFIC_SCOPE_MATHML,
        Namespaces::SVG    => self::SPECIFIC_SCOPE_SVG,
    ];

    private const TABLE_SCOPE = [Namespaces::HTML => ['html' => false, 'table' => false, 'template' => false]];

    /**
     * The number of HTMLTemplateElements on the stack.
     */
    private int $templateElementCount;

    public function __construct()
    {
        parent::__construct();

        $this->templateElementCount = 0;
    }

    public function push($item): void
    {
        parent::push($item);

        if ($item instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }
    }

    public function pop()
    {
        $popped = parent::pop();

        if ($popped instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }

        return $popped;
    }

    public function top()
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        return $this->stack[0];
    }

    public function bottom()
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        return $this->stack[$this->size - 1];
    }

    public function remove($item): void
    {
        parent::remove($item);

        if ($item instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }
    }

    public function replace($newItem, $oldItem): void
    {
        parent::replace($newItem, $oldItem);

        if ($newItem instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }

        if ($oldItem instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }
    }

    /**
     * @param \Rowbot\DOM\Element\Element $newItem
     * @param \Rowbot\DOM\Element\Element $oldItem
     */
    public function insertAfter($newItem, $oldItem): void
    {
        $oldItemId = spl_object_id($oldItem);

        if (!isset($this->cache[$oldItemId])) {
            throw new NotInCollectionException();
        }

        $newItemId = spl_object_id($newItem);

        if (isset($this->cache[$newItemId])) {
            throw new DuplicateItemException();
        }

        if ($newItem instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }

        $this->cache[$newItemId] = true;
        ++$this->size;

        if ($this->stack[$this->size - 2] === $oldItem) {
            array_push($this->stack, $newItem);

            return;
        }

        $index = array_search($oldItem, $this->stack, true);
        array_splice($this->stack, $index + 1, 0, [$newItem]);
    }

    /**
     * Returns true if the stack contains a template element, false otherwise.
     */
    public function containsTemplateElement(): bool
    {
        return $this->templateElementCount > 0;
    }

    /**
     * Pops nodes off the stack of open elements until it finds a thead, tfoot,
     * tbody, template, or html element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#clear-the-stack-back-to-a-table-body-context
     */
    public function clearBackToTableBodyContext(): void
    {
        $size = $this->size;

        while ($size--) {
            $currentNode = $this->stack[$size];

            if (
                $currentNode instanceof HTMLTableSectionElement
                || $currentNode instanceof HTMLTemplateElement
                || $currentNode instanceof HTMLHtmlElement
            ) {
                break;
            }

            $this->pop();
        }
    }

    /**
     * Pops nodes off the stack of open elements until it finds a table,
     * template, or html element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#clear-the-stack-back-to-a-table-context
     */
    public function clearBackToTableContext(): void
    {
        $size = $this->size;

        while ($size--) {
            $currentNode = $this->stack[$size];

            if (
                $currentNode instanceof HTMLTableElement
                || $currentNode instanceof HTMLTemplateElement
                || $currentNode instanceof HTMLHtmlElement
            ) {
                break;
            }

            $this->pop();
        }
    }

    /**
     * Pops nodes off the stack of open elements until it finds a tr, template,
     * or html element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#clear-the-stack-back-to-a-table-row-context
     */
    public function clearBackToTableRowContext(): void
    {
        $size = $this->size;

        while ($size--) {
            $currentNode = $this->stack[$size];

            if (
                $currentNode instanceof HTMLTableRowElement
                || $currentNode instanceof HTMLTemplateElement
                || $currentNode instanceof HTMLHtmlElement
            ) {
                break;
            }

            $this->pop();
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-the-specific-scope
     *
     * @param array<string, array<string, bool>> $list
     */
    private function hasElementInSpecificScope(string $tagName, array $list): bool
    {
        $size = $this->size;

        while ($size--) {
            // 1. Initialize node to be the current node (the bottommost node of the stack).
            $node = $this->stack[$size];
            $namespace = $node->namespaceURI;
            $localName = $node->localName;

            // 2. If node is the target node, terminate in a match state.
            if ($namespace === Namespaces::HTML && $localName === $tagName) {
                return true;
            }

            // 3. Otherwise, if node is one of the element types in list, terminate in a failure state.
            if (isset($list[$namespace][$localName])) {
                return false;
            }

            // 4. Otherwise, set node to the previous entry in the stack of open elements and return to step 2. (This
            // will never fail, since the loop will always terminate in the previous step if the top of the stack — an
            // html element — is reached.)
        }

        assert(false, 'Should not reach here.');

        return false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-scope
     */
    public function hasElementInScope(string $tagName, string $namespace): bool
    {
        return $this->hasElementInSpecificScope($tagName, self::SPECIFIC_SCOPE);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-list-item-scope
     */
    public function hasElementInListItemScope(string $tagName, string $namespace): bool
    {
        return $this->hasElementInSpecificScope($tagName, self::LIST_ITEM_SCOPE);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-button-scope
     */
    public function hasElementInButtonScope(string $tagName, string $namespace): bool
    {
        return $this->hasElementInSpecificScope($tagName, self::BUTTON_SCOPE);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-table-scope
     */
    public function hasElementInTableScope(string $tagName, string $namespace): bool
    {
        return $this->hasElementInSpecificScope($tagName, self::TABLE_SCOPE);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-select-scope
     */
    public function hasElementInSelectScope(string $tagName, string $namespace): bool
    {
        $size = $this->size;

        while ($size--) {
            $node = $this->stack[$size];
            $ns = $node->namespaceURI;
            $localName = $node->localName;

            if ($namespace === $ns && $localName === $tagName) {
                return true;
            }

            if (
                !(
                    $namespace === Namespaces::HTML
                    && ($localName === 'optgroup' || $localName === 'option')
                )
            ) {
                return false;
            }
        }

        return false;
    }
}
