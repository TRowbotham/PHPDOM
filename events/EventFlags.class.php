<?php
abstract class EventFlags
{
    const STOP_PROPAGATION = 1;
    const STOP_IMMEDIATE_PROPAGATION = 2;
    const CANCELED = 4;
    const IN_PASSIVE_LISTENER = 8;
    const COMPOSED = 16;
    const INTIALIZED = 32;
    const DISPATCH = 64;
}
