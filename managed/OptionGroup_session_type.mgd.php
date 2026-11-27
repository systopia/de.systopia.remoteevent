<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_session_type',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'session_type',
        'title' => E::ts('Session Type'),
        'description' => E::ts('Defines the type of the session'),
        'option_value_fields' => [
          'name',
          'label',
          'description',
        ],
        'is_active' => TRUE,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_type_OptionValue_Session',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_type',
        'label' => E::ts('Session'),
        'name' => 'Session',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_type_OptionValue_Workshop',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_type',
        'label' => E::ts('Workshop'),
        'name' => 'Workshop',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_type_OptionValue_Talk',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_type',
        'label' => E::ts('Talk'),
        'name' => 'Talk',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_type_OptionValue_Keynote',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_type',
        'label' => E::ts('Keynote'),
        'name' => 'Keynote',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_type_OptionValue_Presentation',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_type',
        'label' => E::ts('Presentation'),
        'name' => 'Presentation',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
];
