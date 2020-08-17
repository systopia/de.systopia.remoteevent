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

use CRM_Remoteevent_ExtensionUtil as E;


/**
 * Basic function regarding remote events
 */
class CRM_Remoteevent_RemoteEvent
{
    /** @var array cached events, indexed by event_id */
    protected static $event_cache = [];

    /**
     * Get the given event data
     *
     * @param integer $event_id
     *   event ID
     *
     * @return array
     *   event data
     */
    public static function getRemoteEvent($event_id)
    {
        $event_id = (int) $event_id;
        if (!$event_id) {
            return null;
        }

        if (!isset(self::$event_cache[$event_id])) {
            self::$event_cache[$event_id] = civicrm_api3('RemoteEvent', 'getsingle', [
                'id' => $event_id
            ]);
        }
        return self::$event_cache[$event_id];
    }
}
