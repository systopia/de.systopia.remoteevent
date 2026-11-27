<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_session_category',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'session_category',
        'title' => E::ts('Category or track of your session'),
        'description' => E::ts('Can be used to distinguish session tracks'),
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
    'name' => 'OptionGroup_session_category_OptionValue_Blue',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_category',
        'label' => E::ts('Blue Track'),
        'name' => 'Blue',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_category_OptionValue_Green',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_category',
        'label' => E::ts('Green Track'),
        'name' => 'Green',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_category_OptionValue_Red',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_category',
        'label' => E::ts('Red Track'),
        'name' => 'Red',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_category_OptionValue_Pink',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_category',
        'label' => E::ts('Pink Track'),
        'name' => 'Pink',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
];
