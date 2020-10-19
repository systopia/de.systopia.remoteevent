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
use \Civi\RemoteEvent\Event\GetResultEvent as GetResultEvent;
use \Civi\RemoteEvent\Event\GetFieldsEvent as GetFieldsEvent;

/**
 * Functionality around the speakers of the event
 */
class CRM_Remoteevent_EventSpeaker
{
    const SPEAKER_FIELDS = [
        'roles',
        'name',
        'first_name',
        'last_name',
        'prefix',
    ];

    /**
     * Extend the data returned by RemoteEvent.get by the location data
     *
     * @todo l10n
     *
     * @param GetResultEvent $result
     */
    public static function addSpeakerData(GetResultEvent $result)
    {
        // see if speakers are turned on
        $speaker_roles = Civi::settings()->get('remote_registration_speaker_roles');
        if (empty($speaker_roles) || !is_array($speaker_roles)) {
            foreach ($result->getEventData() as &$event) {
                $event['speakers'] = 'false'; // mark as disabled
            }
            return;
        }

        // extract event_ids
        $events = [];
        foreach ($result->getEventData() as &$event) {
            $event['speakers'] = '[]'; // set default to none
            $events[$event['id']] = &$event;
        }
        if (empty($events)) {
            // nothing to do
            return;
        }

        // get the data
        $speakers_by_event = self::getSpeakersByEvent(array_keys($events), $speaker_roles);
        foreach ($speakers_by_event as $event_id => $speakers) {
            $events[$event_id]['speakers'] = json_encode($speakers);
        }
    }

    /**
     * Add the fields to the RemoteEvent.get_fields list
     *
     * @param GetFieldsEvent $fields_collection
     */
    public static function addFieldSpecs($fields_collection)
    {
        $fields_collection->setFieldSpec('speakers', [
            'name'          => 'speakers',
            'type'          => CRM_Utils_Type::T_STRING,
            'format'        => 'json',
            'title'         => "Speaker List",
            'description'   => "List of speakers of the event (json encoded)",
            'localizable'   => 1,
            'is_core_field' => false,
        ]);
    }

    /**
     * @param array $event_ids
     *  list of events (event_id => event) to find speakers for
     *
     * @return array
     *   event_id => speaker list
     */
    protected static function getSpeakersByEvent($event_ids, $role_ids)
    {
        // make sure it's all INTs
        $event_id_list = implode(',', array_map('intval', $event_ids));

        // get the role condition
        $role_conditions = [];
        $value_separator = CRM_Core_DAO::VALUE_SEPARATOR;
        foreach ($role_ids as $role_id) {
            $role_id = (int) $role_id;
            // remark: unlike other padded fields, this one drops the padding in the front or back
            $role_conditions[] = "participant.role_id REGEXP '(^|{$value_separator}){$role_id}($|{$value_separator})'";
//            $role_conditions[] = "participant.role_id = '{$role_id}'";
//            $role_conditions[] = "participant.role_id LIKE '%{$value_separator}{$role_id}{$value_separator}%'";
//            $role_conditions[] = "participant.role_id LIKE '{$role_id}{$value_separator}%'";
//            $role_conditions[] = "participant.role_id LIKE '%{$value_separator}{$role_id}'";
        }
        $ROLE_CONDITION = implode(' OR ', $role_conditions);

        // build the query
        $query = "
        SELECT
            participant.event_id              AS event_id,
            contact.display_name              AS name,
            contact.id                        AS contact_id,
            contact.first_name                AS first_name,
            contact.last_name                 AS last_name,
            GROUP_CONCAT(participant.role_id) AS roles
        FROM civicrm_participant participant
        LEFT JOIN civicrm_contact contact
               ON contact.id = participant.contact_id
        LEFT JOIN civicrm_participant_status_type status_type
               ON status_type.id = participant.status_id
        WHERE participant.event_id IN ({$event_id_list})
          AND ({$ROLE_CONDITION})
          AND status_type.class IN ('Positive', 'Pending')
        GROUP BY participant.event_id, participant.contact_id
        ";
        $speaker = CRM_Core_DAO::executeQuery($query);
        $speakers = [];
        while ($speaker->fetch()) {
            $speakers[$speaker->event_id][] = [
                'name'       => $speaker->name,
                'contact_id' => $speaker->contact_id,
                'first_name' => $speaker->first_name,
                'last_name'  => $speaker->last_name,
                'roles'      => self::translateRoles($speaker->roles, $role_ids),
            ];
        }

        return $speakers;
    }


    /**
     * Get a comma separated list of speaker roles
     *
     * @param string $field_value
     *   the padded value as stored in the DB
     * @param array $speaker_roles
     *   the list of roles that are considered speakers
     *
     * @return string list of roles
     */
    protected static function translateRoles($field_value, $speaker_roles)
    {
        // first of all, it's possible the roles have been GROUP_CONCAT'ed
        $all_roles = [];
        foreach (explode(',', $field_value) as $roles_blob) {
            $roles = CRM_Utils_Array::explodePadded($roles_blob);
            $all_roles = array_merge($all_roles, $roles);
        }

        // filter to the ones that actually count
        $relevant_roles = array_intersect($all_roles, $speaker_roles);

        // now translate those roles to text
        if (!empty($relevant_roles)) {
            // todo: l10n
            $all_roles = CRM_Remoteevent_EventCache::getRoles();
            $roles_names = [];
            foreach ($relevant_roles as $relevant_role_id) {
                $roles_names[] = $all_roles[$relevant_role_id];
            }
            return implode(', ', $roles_names);

        } else {
            return '';
        }
    }

}
