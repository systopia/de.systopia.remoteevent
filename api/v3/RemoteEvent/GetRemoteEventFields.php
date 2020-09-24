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
 *
 * RemoteEvent.getfields implementation using the CRM_Remotetools_GetFieldsWrapper
 */
function civicrm_api3_remote_event_get_remote_event_fields($params) {
    // get event fields
    unset($params['check_permissions']);
    $fields = civicrm_api3('Event', 'getfields');

    // strip some fields
    foreach (CRM_Remoteevent_RemoteEvent::STRIP_FIELDS as $field_name) {
        unset($fields['values'][$field_name]);
    }

    // resolve custom fields
    CRM_Remoteevent_CustomData::labelCustomFields($fields['values']);
    foreach ($fields['values'] as $field_name => &$field_data) {
        if ($field_name != $field_data['name']) {
            $field_data['name'] = $field_name;
        }
    }

    // add artificial fields
    foreach (CRM_Remoteevent_EventFlags::EVENT_FLAGS as $flag_name) {
        $fields['values'][$flag_name] = [
            'name'          => $flag_name,
            'type'          => CRM_Utils_Type::T_BOOLEAN,
            'title'         => "{$flag_name} flag",
            'localizable'   => 0,
            'is_core_field' => false,
        ];
    }

    // registration_count
    $fields['values']['registration_count'] = [
        'name'          => 'registration_count',
        'type'          => CRM_Utils_Type::T_INT,
        'title'         => "Registration Count",
        'description'   => "Number of currently registered participants",
        'localizable'   => 0,
        'is_core_field' => false,
    ];

    // todo: use symfony event to add fields

    return $fields;
}
