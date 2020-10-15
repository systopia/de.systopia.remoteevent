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
use Civi\RemoteEvent\Event\GetFieldsEvent as GetFieldsEvent;

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
    CRM_Remoteevent_CustomData::labelCustomFields($fields['values'], 1, CRM_Remoteevent_RemoteEvent::API_SEPARATOR);
    foreach ($fields['values'] as $field_name => &$field_data) {
        if ($field_name != $field_data['name']) {
            $field_data['name'] = $field_name;
        }
    }

    // create event to collect more fields
    $fields_collection = new GetFieldsEvent($fields['values']);

    // add artificial fields
    foreach (CRM_Remoteevent_EventFlags::EVENT_FLAGS as $flag_name) {
        $fields_collection->setFieldSpec($flag_name, [
            'name'          => $flag_name,
            'type'          => CRM_Utils_Type::T_BOOLEAN,
            'title'         => "{$flag_name} flag",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
    }
    $fields_collection->setFieldSpec('registration_count', [
        'name'          => 'registration_count',
        'type'          => CRM_Utils_Type::T_INT,
        'title'         => "Number of (positive) registrations",
        'localizable'   => 0,
        'is_core_field' => false,
    ]);
    $fields_collection->setFieldSpec('participant_registration_count', [
        'name'          => 'participant_registration_count',
        'type'          => CRM_Utils_Type::T_INT,
        'title'         => "Number of (positive/pending) registrations for the given contact (if given)",
        'localizable'   => 0,
        'is_core_field' => false,
    ]);

    // dispatch to others
    Civi::dispatcher()->dispatch('civi.remoteevent.getfields', $fields_collection);

    // set results and return
    $fields['values'] = $fields_collection->getFieldSpecs();
    return $fields;
}
