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
class CRM_Remoteevent_EventCache
{
    /** @var array cached events, indexed by event_id */
    protected static $event_cache = [];

    /**
     * Get the given event data.
     *
     *  Warning: uses RemoteEvent.get, don't cause recursions!
     *
     * @param integer $event_id
     *   event ID
     *
     * @return array
     *   event data
     */
    public static function getEvent($event_id)
    {
        $event_id = (int) $event_id;
        if (!$event_id) {
            return null;
        }

        if (!isset(self::$event_cache[$event_id])) {
            self::$event_cache[$event_id] = civicrm_api3('Event', 'getsingle', [
                'id' => $event_id
            ]);
        }
        return self::$event_cache[$event_id];
    }

    /**
     * Cache a given event
     *
     * @param array $event_data
     *   event data. Must contain 'id'
     */
    public static function cacheEvent($event_data) {
        if (!empty($event_data['id'])) {
            self::$event_cache[$event_data['id']] = $event_data;
        }
    }

    /**
     * Get a list of all participant roles
     *
     * @return array
     *   role id => role label
     */
    public static function getRoles() {
        static $list = null;
        if ($list === null) {
            $list = [];
            $query = civicrm_api3('OptionValue', 'get', [
                'option.limit'    => 0,
                'option_group_id' => 'participant_role',
                'return'          => 'value,label'
            ]);
            foreach ($query['values'] as $role) {
                $list[$role['value']] = $role['label'];
            }
        }
        return $list;
    }
}
