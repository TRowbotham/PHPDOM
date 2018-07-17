<?php
namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/media.html#htmlmediaelement
 */
abstract class HTMLMediaElement extends HTMLElement
{
    public const HAVE_NOTHING      = 0;
    public const HAVE_METADATA     = 1;
    public const HAVE_CURRENT_DATA = 2;
    public const HAVE_FUTURE_DATA  = 3;
    public const HAVE_ENOUGH_DATA  = 4;
}
