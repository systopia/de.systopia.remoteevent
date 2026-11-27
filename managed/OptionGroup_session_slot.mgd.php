<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_session_slot',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'session_slot',
        'title' => E::ts('Slots for event sessions'),
        'description' => E::ts('Event sessions can be grouped by these slots'),
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
    'name' => 'OptionGroup_session_slot_OptionValue_Morning',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_slot',
        'label' => E::ts('Morning Slot'),
        'name' => 'Morning',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_slot_OptionValue_Pre_Lunch',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_slot',
        'label' => E::ts('Pre-Lunch Slot'),
        'name' => 'Pre-Lunch',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_slot_OptionValue_Post_Lunch',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_slot',
        'label' => E::ts('Post-Lunch Slot'),
        'name' => 'Post-Lunch',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_slot_OptionValue_Afternoon',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_slot',
        'label' => E::ts('Afternoon Slot'),
        'name' => 'Afternoon',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_session_slot_OptionValue_Closing',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'session_slot',
        'label' => E::ts('Closing Slot'),
        'name' => 'Closing',
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
];
