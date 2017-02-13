<?php
namespace phpjs\parser;

use phpjs\elements\Element;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-list-of-active-formatting-elements
 */
class ActiveFormattingElementsStack extends ElementStack
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Pops all entries in the list of active formatting elements up to the
     * next marker.
     *
     * @see https://html.spec.whatwg.org/multipage/#clear-the-list-of-active-formatting-elements-up-to-the-last-marker
     */
    public function clearUpToLastMarker()
    {
        while ($this->length > 0) {
            $entry = parent::pop();

            if ($entry instanceof Marker) {
                break;
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#push-onto-the-list-of-active-formatting-elements
     * @param  [type] $aValue [description]
     * @return [type]         [description]
     */
    public function push($aValue)
    {
        $isElement = $aValue instanceof Element;

        if (!$isElement && !($aValue instanceof Marker)) {
            throw new ParserException(
                'The pushed object must either be an Element or a Marker'
            );
        }

        if ($isElement) {
            $namespace = $aValue->namespaceURI;
            $tagName = $aValue->tagName;
            $attributes = $aValue->getAttributeList();
            $count = 0;

            for ($i = $this->length - 1; $i >= 0; $i--) {
                $key = $this->keys[$i];
                $obj = $this->map[$key];

                if ($obj instanceof Marker) {
                    break;
                }

                if ($obj->namespaceURI === $namespace &&
                    $obj->tagName === $tagName
                ) {
                    foreach ($attributes as $attr) {
                        $attrNamespace = $attr->namespaceURI;
                        $attrNode = $obj->getAttributeNodeNS(
                            $attrNamespace,
                            $attr->localName
                        );

                        if (!$attrNode ||
                            ($attrNode->name !== $attr->name &&
                                $attrNode->namespaceURI !== $attrNamespace &&
                                $attrNode->value !== $attr->value)
                        ) {
                            continue 2;
                        }
                    }

                    $element = $obj;
                    $count++;
                }
            }

            if ($count > 3) {
                parent::remove($element);
            }
        }

        parent::push($aValue);
    }
}
