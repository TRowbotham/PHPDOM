<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Text;

class HTMLTitleElement extends HTMLElement
{
    protected function __construct()
    {
        parent::__construct();
    }

    public function __get($name)
    {
        switch ($name) {
            case 'text':
                $value = '';

                foreach ($this->childNodes as $node) {
                    if ($node instanceof Text) {
                        $value .= $node->data;
                    }
                }

                return $value;

            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'text':
                parent::__set('textContent', $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
