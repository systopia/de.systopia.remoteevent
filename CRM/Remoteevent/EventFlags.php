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
 * Various Flags in events
 */
class CRM_Remoteevent_EventFlags
{
    /** @var string[] list of additional event flags added by RemoteEvent */
    const EVENT_FLAGS = [
        'can_register',
        'can_instant_register',
        'can_edit_registration',
        'can_cancel_registration',
        'is_registered',
    ];

    /** @var string[] list of flags in the event configuration relevant for the UI (full name => internal name) */
    const EVENT_CONFIG_FLAGS = [
        'event_remote_registration.remote_registration_enabled'           => 'remote_registration_enabled',
        'event_remote_registration.remote_use_custom_event_location'      => 'remote_use_custom_event_location',
        'event_remote_registration.remote_disable_civicrm_registration'   => 'remote_disable_civicrm_registration',
        'requires_approval'                                               => 'requires_approval',
    ];

    /**
     * Get all flags for this event
     *
     * @param integer $event_id
     *   the event ID
     *
     * @return array
     *   event data
     */
    public static function getEventFlags($event_id)
    {
        static $event_data = [];
        if (CRM_Utils_Array::value('id', $event_data) != $event_id) {
            // todo: have a common pool for cached events?
            // load event data
            $flag_fields = self::EVENT_CONFIG_FLAGS;
            CRM_Remoteevent_CustomData::resolveCustomFields($flag_fields);
            $event_data = civicrm_api3('Event', 'getsingle', [
                'id'     => $event_id,
                'return' => implode(',', array_keys($flag_fields))
            ]);
            CRM_Remoteevent_CustomData::labelCustomFields($event_data);

            // fill up missing fields (i.e. flag is false)
            foreach (array_keys(self::EVENT_CONFIG_FLAGS) as $field_name) {
                if (!isset($event_data[$field_name])) {
                    $event_data[$field_name] = 0;
                }
            }
        }
        return $event_data;
    }

    /**
     * Check if the alternative location is active in this event
     *
     * @param integer $event_id
     *   the event ID
     *
     * @return boolean
     */
    public static function isRemoteRegistrationEnabled($event_id)
    {
        $event_data = self::getEventFlags($event_id);
        return CRM_Utils_Array::value('event_remote_registration.remote_registration_enabled', $event_data, false);
    }

    /**
     * Check if the alternative location is active in this event
     *
     * @param integer $event_id
     *   the event ID
     *
     * @return boolean
     */
    public static function isAlternativeLocationEnabled($event_id)
    {
        $event_data = self::getEventFlags($event_id);
        return CRM_Utils_Array::value('event_remote_registration.remote_use_custom_event_location', $event_data, false);
    }

    /**
     * Check if the native CiviCRM registration is removed
     *
     * @param integer $event_id
     *   the event ID
     *
     * @return boolean
     */
    public static function isNativeOnlineRegistrationDisabled($event_id)
    {
        $event_data = self::getEventFlags($event_id);
        return CRM_Utils_Array::value('event_remote_registration.remote_disable_civicrm_registration', $event_data, false);
    }
}
