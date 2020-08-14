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


namespace Civi\RemoteEvent\Event;

/**
 * Class GetResultEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event will be triggered to manipulate and extend the
 *   output of RemoteEvent.get
 */
class GetResultEvent extends \Symfony\Component\EventDispatcher\Event {

    /** @var array holds the original RemoteEvent.get parameters */
    protected $params;

    /** @var array holds the RemoteEvent.get parameters to be applied */
    protected $event_data;

    /**
     * GetResultEvent constructor.
     * @param array $params
     *   the parameters of the Event.get query
     *
     * @param array $values
     *   the list of events to return
     */
    public function __construct($params, $event_data)
    {
        $this->params = $params;
        $this->event_data = $event_data;
    }

    /**
     * Returns the current (manipulated) parameters to be submitted to Event.get
     *
     * @return array current parameters
     */
    public function getEventData() {
        return $this->event_data;
    }
}
