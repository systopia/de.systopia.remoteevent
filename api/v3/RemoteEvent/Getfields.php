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

declare(strict_types = 1);

use CRM_Remoteevent_ExtensionUtil as E;
use Civi\RemoteEvent\Event\GetFieldsEvent as GetFieldsEvent;

/**
 *
 * RemoteEvent.getfields
 */
function civicrm_api3_remote_event_getfields($params) {
  unset($params['check_permissions']);

  switch (strtolower($params['action'])) {
    case 'get':
    case 'getsingle':
    case 'getcount':
      return civicrm_api3_remote_event_getfields_get($params);

    default:
      # default is to return the Event standards
      return civicrm_api3('Event', 'getfields', $params);
  }
}

/**
 * Customised RemoteEvent.getfields: spawn (create)
 *
 * @param array $params
 *   parameters of the original call
 *
 * @return array
 *   fields array
 */
function civicrm_api3_remote_event_getfields_spawn($params) {
  // get event fields
  $fields = civicrm_api3('Event', 'getfields', ['action' => 'create'])['values'];

  // use API sanitation / labelling
  CRM_Remotetools_CustomData::labelCustomFields($fields);
  foreach ($fields as $name => &$field) {
    $field['name'] = $name;
  }

  // own fields:
  // 1) template ID
  $fields['template_id'] = [
    'name'         => 'template_id',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => E::ts('Template ID'),
    'description'  => E::ts('If the ID of an existing event or event template is given, the new event will be based on that.'),
  ];

  // remove some stuff
  // disable updates
  unset($fields['id']);
  // no template handling here
  unset($fields['is_template']);

  return civicrm_api3_create_success($fields);
}

/**
 * Customised RemoteEvent.getfields: get/getsingle
 *
 * @param array $params
 *   parameters of the original call
 *
 * @return array
 *   fields array
 */
function civicrm_api3_remote_event_getfields_get($params) {
  // get event fields
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

  // create event to collect more fields
  $fields_collection = new GetFieldsEvent($fields['values']);

  // add artificial fields
  foreach (CRM_Remoteevent_EventFlags::EVENT_FLAGS as $flag_name) {
    $fields_collection->setFieldSpec($flag_name, [
      'name'          => $flag_name,
      'type'          => CRM_Utils_Type::T_BOOLEAN,
      'title'         => "{$flag_name} flag",
      'localizable'   => 0,
      'is_core_field' => FALSE,
    ]);
  }
  $fields_collection->setFieldSpec('registration_count', [
    'name'          => 'registration_count',
    'type'          => CRM_Utils_Type::T_INT,
    'title'         => 'Number of (positive) registrations',
    'localizable'   => 0,
    'is_core_field' => FALSE,
  ]);
  $fields_collection->setFieldSpec('participant_registration_count', [
    'name'          => 'participant_registration_count',
    'type'          => CRM_Utils_Type::T_INT,
    'title'         => 'Number of (positive/pending) registrations for the given contact (if given)',
    'localizable'   => 0,
    'is_core_field' => FALSE,
  ]);
  $fields_collection->setFieldSpec('event_type', [
    'name'          => 'event_type',
    'type'          => CRM_Utils_Type::T_STRING,
    'title'         => 'Event Type Label',
    'localizable'   => 1,
    'is_core_field' => FALSE,
  ]);

  // dispatch to others
  Civi::dispatcher()->dispatch(GetFieldsEvent::NAME, $fields_collection);

  // finally: add options to all the relevant fields
  foreach ($fields_collection->getFieldSpecs() as $field_name => $fieldSpec) {
    if (!empty($fieldSpec['pseudoconstant']['optionGroupName']) && empty($fieldSpec['options'])) {
      $fieldSpec['options'] = CRM_Remoteevent_Tools::getOptions(
        $fieldSpec['pseudoconstant']['optionGroupName'],
        $fields_collection->getLocale(),
        ['is_reserved' => 0]
      );
      $fields_collection->setFieldSpec($field_name, $fieldSpec);
    }
  }

  // set results and return
  $fields['values'] = $fields_collection->getFieldSpecs();
  return $fields;
}
