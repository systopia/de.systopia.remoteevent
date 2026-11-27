<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
  [
    'name' => 'CustomGroup_event_alternative_location',
    'entity' => 'CustomGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'event_alternative_location',
        'table_name' => 'civicrm_value_event_alternative_location',
        'title' => E::ts('Event Location'),
        'extends' => 'Event',
        'style' => 'Tab',
        'collapse_display' => TRUE,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_event_alternative_location_CustomField_event_alternativelocation_contact_id',
    'entity' => 'CustomField',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_alternative_location',
        'name' => 'event_alternativelocation_contact_id',
        'label' => E::ts('Location Contact'),
        'data_type' => 'ContactReference',
        'html_type' => 'Autocomplete-Select',
        'is_searchable' => TRUE,
        'column_name' => 'contact_id',
        'filter' => 'action=get&contact_sub_type=Event_Location',
        'in_selector' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'name',
        'custom_group_id',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_event_alternative_location_CustomField_event_alternativelocation_remark',
    'entity' => 'CustomField',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_alternative_location',
        'name' => 'event_alternativelocation_remark',
        'label' => E::ts('Additional Information'),
        'data_type' => 'Memo',
        'html_type' => 'RichTextEditor',
        'column_name' => 'remark',
        'in_selector' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'name',
        'custom_group_id',
      ],
    ],
  ],
];
