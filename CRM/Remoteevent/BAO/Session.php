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

class CRM_Remoteevent_BAO_Session extends CRM_Remoteevent_DAO_Session
{

    /**
     * Create a new Session based on array-data
     *
     * @param array $params key-value pairs
     *
     * @return CRM_Remoteevent_DAO_Session|NULL
     */
    public static function create($params)
    {
        $className = 'CRM_Remoteevent_DAO_Session';
        $entityName = 'Session';
        $hook = empty($params['id']) ? 'create' : 'edit';

        CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
        $instance = new $className();
        $instance->copyValues($params);
        $instance->save();
        CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

        return $instance;
    }

    /**
     * Copy/clone all the sessions from a given event to another.
     * This is usually triggered when copying an event
     *
     * @param integer $old_event_id
     * @param integer $new_event_id
     */
    public static function copySessions($old_event_id, $new_event_id)
    {
        $old_event_id = (int)$old_event_id;
        $new_event_id = (int)$new_event_id;
        self::executeQuery(
            "
       INSERT INTO civicrm_session (event_id,title,is_active,start_date,end_date,slot_id,category_id,type_id,description,max_participants,location,presenter_id,presenter_title)
       SELECT {$new_event_id} AS event_id,title,is_active,start_date,end_date,slot_id,category_id,type_id,description,max_participants,location,presenter_id,presenter_title
       FROM civicrm_session
       WHERE event_id = {$old_event_id}
      "
        );
    }

    /**
     * Check if the given event has sessions
     *
     * @param integer $event_id
     *  event ID
     *
     * @return bool
     *   does the event have sessions?
     */
    public static function eventHasSessions($event_id)
    {
        $event_id = (int)$event_id;
        if ($event_id) {
            $session_count = civicrm_api3('Session', 'getcount', ['event_id' => $event_id]);
            return $session_count > 0;
        } else {
            return false;
        }
    }

    /**
     * Get a list of sessions as property arrays,
     *  with additional attributes such as
     *   'day': number of day (1 = first, 2 = second, ...)
     *
     * @param integer $event_id
     *  event ID
     *
     * @param boolean $cached
     *  use a cached result, or reload (and refresh cache)
     */
    public static function getSessions($event_id, $cached = true)
    {
        $event_id = (int) $event_id;
        // handle caching
        static $session_cache = [];
        if ($cached && isset($session_cache[$event_id])) {
            return $session_cache[$event_id];
        }

        // first: get the start date
        try {
            $event_start_date = civicrm_api3('Event', 'getvalue', [
                'id'     => $event_id,
                'return' => 'start_date']);
            $event_start_date = strtotime($event_start_date);
        } catch (CiviCRM_API3_Exception $ex) {
            // something's wrong
            return [];
        }

        // now load all sessions
        $session_list = [];
        $sessions_raw = civicrm_api3('Session', 'get', [
            'event_id'     => $event_id,
            'option.limit' => 0,
            'option.sort'  => 'start_date asc'
        ])['values'];

        foreach ($sessions_raw as $session) {
            // calculate day of event
            $session['day'] = 1 + (strtotime($session['start_date']) -  $event_start_date) / (60 * 60 * 24);

            // store
            $session_list[$session['id']] = $session;
        }

        // cache
        $session_cache[$event_id] = $session_list;

        return $session_list;
    }
}
