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
     *  ordered by start_date,
     *  with additional attributes such as
     *   'day': number of day (1 = first, 2 = second, ...)
     *
     * @param integer $event_id
     *  event ID
     *
     * @param boolean $cached
     *  use a cached result, or reload (and refresh cache)
     *
     * @param string $start_date
     *  event start_date. if empty, will be loaded from the event
     */
    public static function getSessions($event_id, $cached = true, $start_date = null)
    {
        $event_id = (int) $event_id;
        // handle caching
        static $session_cache = [];
        if ($cached && isset($session_cache[$event_id])) {
            return $session_cache[$event_id];
        }

        // first: get the start date if it's not passed
        if (empty($start_date)) {
            try {
                $start_date = civicrm_api3('Event', 'getvalue', [
                    'id'     => $event_id,
                    'return' => 'start_date']);
            } catch (CiviCRM_API3_Exception $ex) {
                // something's wrong
                return [];
            }
        }
        $start_date = strtotime($start_date);

        // now load all sessions
        $session_list = [];
        $sessions_raw = civicrm_api3('Session', 'get', [
            'event_id'     => $event_id,
            'option.limit' => 0,
            'option.sort'  => 'start_date asc'
        ])['values'];

        foreach ($sessions_raw as $session) {
            // calculate day of event
            $session['day'] = 1 + (int) ((strtotime($session['start_date']) - $start_date) / (60 * 60 * 24));

            // store
            $session_list[$session['id']] = $session;
        }

        // cache
        $session_cache[$event_id] = $session_list;

        return $session_list;
    }

    /**
     * Get the participant count for all sessions of the given event
     *
     * @param integer $event_id
     *   event id
     *
     * @return array
     *   [session_id => participant_count]
     */
    public static function getParticipantCounts($event_id)
    {
        $event_id = (int) $event_id;

        // run this as a sql query
        $participant_counts = [];
        $participant_query = CRM_Core_DAO::executeQuery("
            SELECT
             session.id            AS session_id,
             COUNT(participant.id) AS participant_count
            FROM civicrm_session session
            LEFT JOIN civicrm_participant_session participant
                   ON participant.session_id = session.id
            WHERE session.event_id = {$event_id}
            GROUP BY session.id");
        while ($participant_query->fetch()) {
            $participant_counts[$participant_query->session_id] = $participant_query->participant_count;
        }
        return $participant_counts;
    }

    /**
     * Get all current registrations for the given participant and event
     *
     * @param integer $event_id
     *   event id
     *
     * @param integer $participant_id
     *   participant id
     *
     * @return array
     *   list of session IDs
     */
    public static function getParticipantRegistrations($event_id, $participant_id)
    {
        $event_id = (int) $event_id;
        $participant_id = (int) $participant_id;

        // run this as a sql query
        $session_ids = [];
        $participant_query = CRM_Core_DAO::executeQuery("
            SELECT
             session_id AS session_id
            FROM civicrm_participant_session participant
            WHERE event_id = {$event_id}
              AND participant_id = {$participant_id}
            ");
        while ($participant_query->fetch()) {
            $session_ids[] = $participant_query->session_id;
        }
        return $session_ids;
    }


    /**
     * Get the label of the session category
     *
     * @param integer $session_category_id
     *   the category id
     *
     * @return string
     *   resolved label
     */
    public static function getSessionCategoryLabel($session_category_id)
    {
        // gather categories
        static $categories = null;
        if ($categories === null) {
            $categories = [];
            $data = civicrm_api3('OptionValue', 'get', [
                'option_group_id' => 'session_category',
                'option.limit'    => 0,
                'return'          => 'value,label'
            ]);
            foreach ($data['values'] as $category) {
                $categories[$category['value']] = $category['label'];
            }
        }

        // resolve
        return CRM_Utils_Array::value($session_category_id, $categories, '');
    }

    /**
     * Get the label of the session category
     *
     * @param integer $session_type_id
     *   the category id
     *
     * @return string
     *   resolved label
     */
    public static function getSessionTypeLabel($session_type_id)
    {
        // gather types
        static $types = null;
        if ($types === null) {
            $types = [];
            $data = civicrm_api3('OptionValue', 'get', [
                'option_group_id' => 'session_type',
                'option.limit'    => 0,
                'return'          => 'value,label'
            ]);
            foreach ($data['values'] as $type) {
                $types[$type['value']] = $type['label'];
            }
        }

        // resolve
        return CRM_Utils_Array::value($session_type_id, $types, '');
    }

    /**
     * Get the label of the session category
     *
     * @param integer $slot_id
     *   the slot id
     *
     * @return string
     *   resolved label
     */
    public static function getSlotLabel($slot_id)
    {
        // gather types
        static $slots = null;
        if ($slots === null) {
            $slots = [];
            $data = civicrm_api3('OptionValue', 'get', [
                'option_group_id' => 'session_slot',
                'option.limit'    => 0,
                'return'          => 'value,label'
            ]);
            foreach ($data['values'] as $slot) {
                $slots[$slot['value']] = $slot['label'];
            }
        }

        // resolve
        return CRM_Utils_Array::value($slot_id, $slots, '');
    }
}
