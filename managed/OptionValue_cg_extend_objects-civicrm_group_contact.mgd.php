<?php
declare(strict_types = 1);

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
];
