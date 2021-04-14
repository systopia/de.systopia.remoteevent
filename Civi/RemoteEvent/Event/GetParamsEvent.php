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
use Civi\RemoteParamsEvent;

/**
 * Class GetParamsEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteEvent.get API call, so the search parameters can be manipulated
 */
class GetParamsEvent extends RemoteParamsEvent
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->token_usages = ['invite', 'cancel', 'update'];
    }

    /**
     * Get the current restriction of event IDs
     *
     * Remark: this returns
     *   null  if not set
     *   fail  if couldn't be parsed
     *   array with a list of event IDs, if everything's fine
     *
     * @return array|null|string
     *   list of requested IDs
     */
    public function getRequestedEventIDs()
    {
        return $this->getRequestedEntityIDs();
    }

    /**
     * Restrict the query to the given event IDs.
     *  Existing restrictions will be taken into account (intersection)
     *
     * @param array $event_ids
     *   list of event IDs
     */
    public function restrictToEventIds($event_ids)
    {
        $this->restrictToEntityIds($event_ids);
    }
}
