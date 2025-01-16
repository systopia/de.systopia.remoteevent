<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
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
        'is_reserved' => FALSE,
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
