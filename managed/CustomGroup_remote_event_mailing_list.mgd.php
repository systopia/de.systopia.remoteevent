<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
  [
    'name' => 'OptionValue_cg_extend_objects-civicrm_group_contact',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('Group Contact'),
        'value' => 'GroupContact',
        'name' => 'civicrm_group_contact',
        'is_reserved' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_remote_event_mailing_list',
    'entity' => 'CustomGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'remote_event_mailing_list',
        'table_name' => 'civicrm_value_remote_event_mailing_list',
        'title' => E::ts('Remote Event Mailing List'),
        'extends' => 'GroupContact',
        'style' => 'Inline',
        'collapse_display' => TRUE,
        'help_pre' => '',
        'help_post' => '',
        'collapse_adv_display' => TRUE,
        'is_public' => FALSE,
        'is_reserved' => TRUE,
        'icon' => '',
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_remote_event_mailing_list_CustomField_token',
    'entity' => 'CustomField',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'remote_event_mailing_list',
        'name' => 'token',
        'label' => E::ts('Token'),
        'html_type' => 'Text',
        'is_searchable' => TRUE,
        'is_view' => TRUE,
        'text_length' => 255,
        'note_columns' => 60,
        'note_rows' => 4,
        'column_name' => 'token',
      ],
      'match' => [
        'name',
        'custom_group_id',
      ],
    ],
  ],
];
