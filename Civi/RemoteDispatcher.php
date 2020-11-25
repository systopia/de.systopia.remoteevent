<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


namespace Civi;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class RemoteEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * Abstract event class to provide some basic functions
 */
class RemoteDispatcher
{
    /** @var EventDispatcherInterface */
    protected $dispatcher = null;

    public function __construct()
    {
        $this->dispatcher = \Civi::dispatcher();
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string   $eventName The event to listen on
     * @param callable $listener  The listener
     * @param int      $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function addUniqueListener($eventName, $listener, $priority = 0)
    {
        // first remove to avoid duplicate registrations
        $this->dispatcher->removeListener($eventName, $listener);

        // then register the event
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }
}
