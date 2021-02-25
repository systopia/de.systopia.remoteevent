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

    /** @var array cached session data by event_id */
    protected static $session_cache = [];

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
     * Cache the given sessions to be queried with getSessions
     *
     * @param array $event_ids
     *  event IDs
     */
    public static function cacheSessions($event_ids, $event_data)
    {
        // make sure there are no bogous IDs
        $event_ids = array_map('intval', $event_ids);
        if (empty($event_ids)) {
            return;
        }

        // this is what we want to collect and cache
        $session_list_by_event = [];

        // now load all sessions
        $sessions_raw = civicrm_api3('Session', 'get', [
            'event_id'     => ['IN' => $event_ids],
            'option.limit' => 0,
            'option.sort'  => 'start_date asc, id asc',
            //'return'       => 'TODO',
        ])['values'];

        // sort all sessions into the events
        foreach ($sessions_raw as $session) {
            // detached session? skip!
            if (empty($session['event_id'])) {
                continue;
            }

            // get the event start date, so we can the session day
            if (isset($event_data[$session['event_id']]['start_date'])) {
                $event_start_date = strtotime($event_data[$session['event_id']]['start_date']);
            } else {
                Civi::log()->debug('CRM_Remoteevent_BAO_Session:cacheSessions separately loading event start dates. This should not happen, and is very slow.');
                $event_start_date = strtotime(civicrm_api3('Event', 'getvalue', ['return' => 'start_date', 'id' => $session['event_id']]));
            }

            // calculate day of event
            $session['day'] = 1 + (int) ((strtotime($session['start_date']) - $event_start_date) / (60 * 60 * 24));

            // store
            $session_list_by_event[$session['event_id']][] = $session;
        }

        // cache
        foreach ($session_list_by_event as $event_id => $session_list) {
            self::$session_cache[$event_id] = $session_list;
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
        if ($cached && isset(self::$session_cache[$event_id])) {
            return self::$session_cache[$event_id];
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
            'option.sort'  => 'start_date asc, id asc'
        ])['values'];

        foreach ($sessions_raw as $session) {
            // calculate day of event
            $session['day'] = 1 + (int) ((strtotime($session['start_date']) - $start_date) / (60 * 60 * 24));

            // store
            $session_list[$session['id']] = $session;
        }

        // cache
        self::$session_cache[$event_id] = $session_list;

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
            LEFT JOIN civicrm_participant_session participant_session
                   ON participant_session.session_id = session.id
            LEFT JOIN civicrm_participant participant
                   ON participant.id = participant_session.participant_id
            LEFT JOIN civicrm_participant_status_type participant_status
                   ON participant_status.id = participant.status_id
            LEFT JOIN civicrm_contact contact
                   ON contact.id = participant.contact_id
            WHERE session.event_id = {$event_id}
              AND participant_status.is_counted = 1
              AND (contact.is_deleted IS NULL OR contact.is_deleted = 0)
            GROUP BY session.id");
        while ($participant_query->fetch()) {
            $participant_counts[$participant_query->session_id] = $participant_query->participant_count;
        }
        return $participant_counts;
    }

    /**
     * Get all current registrations for the given participant and event
     *
     * @param integer $participant_id
     *   participant id
     *
     * @return array
     *   list of session IDs
     */
    public static function getParticipantRegistrations($participant_id)
    {
        $participant_id = (int) $participant_id;

        // run this as a sql query
        $session_ids = [];
        $participant_query = CRM_Core_DAO::executeQuery("
            SELECT
             session_id AS session_id
            FROM civicrm_participant_session participant
            WHERE participant_id = {$participant_id}
            ");
        while ($participant_query->fetch()) {
            $session_ids[] = $participant_query->session_id;
        }
        return $session_ids;
    }

    /**
     * Get all current participants for the given session
     *
     * @param integer $session_id
     *   session_id id
     *
     * @return array
     *   list of participant data [id, is_counted, contact_id]
     */
    public static function getSessionRegistrations($session_id, $only_counted = false)
    {
        $session_id = (int) $session_id;

        $WHERE_CLAUSE = "session_id = {$session_id}";
        if ($only_counted) {
            $WHERE_CLAUSE .= " HAVING participant_counts = 1";
        }

        // run this as a sql query
        $participants = [];
        $participant_query = CRM_Core_DAO::executeQuery("
            SELECT
                participant.id AS participant_id,
                contact.id     AS contact_id,
                IF(participant_status.is_counted = 1 AND (contact.is_deleted = 0 OR contact.is_deleted IS NULL), 1 , 0)
                               AS participant_counts
            FROM civicrm_participant_session participant_session
            LEFT JOIN civicrm_participant participant
                   ON participant.id = participant_session.participant_id
            LEFT JOIN civicrm_contact contact
                   ON contact.id = participant.contact_id
            LEFT JOIN civicrm_participant_status_type participant_status
                   ON participant_status.id = participant.status_id          
            WHERE {$WHERE_CLAUSE}
            ");
        while ($participant_query->fetch()) {
            $participants[$participant_query->participant_id] = [
                'id'                 => $participant_query->participant_id,
                'contact_id'         => $participant_query->contact_id,
                'participant_counts' => $participant_query->participant_counts
            ];
        }
        return $participants;
    }

    /**
     * Set the session IDs for the given participant
     *
     * Warning: does not check if the sessions and the participant
     *  belong to the same event
     *
     * @param integer $participant_id
     *    the participant
     *
     * @param array $requested_session_ids
     *    the list of session IDs
     */
    public static function setParticipantRegistrations($participant_id, $requested_session_ids) {
        $participant_id = (int) $participant_id;
        $current_session_ids = self::getParticipantRegistrations($participant_id);

        // remove the ones that should go
        $ids_to_remove = array_diff($current_session_ids, $requested_session_ids);
        if (!empty($ids_to_remove)) {
            // make sure it's int
            $ids_to_remove_list = implode(',', array_map('intval', $ids_to_remove));
            CRM_Core_DAO::executeQuery("
                DELETE FROM civicrm_participant_session
                WHERE participant_id = {$participant_id}
                  AND session_id IN ({$ids_to_remove_list});"
            );
        }

        // add the ones that should be there
        $ids_to_add = array_diff($requested_session_ids, $current_session_ids);
        if (!empty($ids_to_add)) {
            $query = "INSERT INTO civicrm_participant_session (participant_id, session_id) VALUES ";
            $inserts = [];
            foreach ($ids_to_add as $id_to_add) {
                $id_to_add = (int)$id_to_add;
                $inserts[] = "({$participant_id}, {$id_to_add})";
            }
            CRM_Core_DAO::executeQuery($query . implode(',', $inserts));
        }
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
