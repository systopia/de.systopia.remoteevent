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

require_once 'remoteevent.civix.php';
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * RemoteEvent.get specification
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_event_get_spec(&$spec)
{
    // let's start with the basic event specs
    require_once 'api/v3/Event.php';
    _civicrm_api3_event_get_spec($spec);

    // todo: modify fields
}

/**
 * RemoteEvent.get implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_remote_event_get($params)
{
    // modify search terms based on user/permission/etc
    $params['is_template'] = 0; // exclude templates
    if (!CRM_Core_Permission::check('view all Remote Events')) {
        // only basic view permissions -> only list public + active
        $params['is_public'] = 1;
        $params['is_active'] = 1;
    }

    // todo: only view the ones that are open for registration?
    $params['event_remote_registration.remote_registration_enabled'] = 1;

    // use the basic event API for queries
    CRM_Remoteevent_CustomData::resolveCustomFields($params);
    $result = civicrm_api3('Event', 'get', $params);
    $event_list = $result['values'];

    // apply custom field labelling
    foreach ($event_list as $key => &$event) {
        CRM_Remoteevent_CustomData::labelCustomFields($event);
    }

    // strip some misleading event data
    $strip_fields = ['is_online_registration','event_full_text','is_map','is_show_location','created_id','created_date'];
    foreach ($event_list as $key => &$event) {
        foreach ($strip_fields as $field_name) {
            unset($event[$field_name]);
        }
    }

    // add profile data
    $profiles = CRM_Remoteevent_RegistrationProfile::getAvailableRegistrationProfiles('name');
    foreach ($event_list as $key => &$event) {
        // set default profile
        if (isset($event['event_remote_registration.remote_registration_default_profile'])) {
            $default_profile_id = (int) $event['event_remote_registration.remote_registration_default_profile'];
            if (isset($profiles[$default_profile_id])) {
                $event['default_profile'] = $profiles[$default_profile_id];
            } else {
                $event['default_profile'] = '';
            }
            unset($event['event_remote_registration.remote_registration_default_profile']);
        }

        // enabled profiles
        $enabled_profiles = $event['event_remote_registration.remote_registration_profiles'];
        $enabled_profile_names = [];
        if (is_array($enabled_profiles)) {
            foreach ($enabled_profiles as $profile_id) {
                if (isset($profiles[$profile_id])) {
                    $enabled_profile_names[] = $profiles[$profile_id];
                }
            }
        }
        $event['enabled_profiles'] = implode(',', $enabled_profile_names);
        unset($event['event_remote_registration.remote_registration_profiles']);

        // also map remote_registration_enabled
        $event['remote_registration_enabled'] = $event['event_remote_registration.remote_registration_enabled'];
        unset($event['event_remote_registration.remote_registration_enabled']);
    }


    return civicrm_api3_create_success($event_list);
}
