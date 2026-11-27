<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
  [
    'name' => 'ParticipantStatusType_Invited',
    'entity' => 'ParticipantStatusType',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Invited',
        'label' => E::ts('Invited'),
        'class' => 'Waiting',
        'is_reserved' => TRUE,
        'visibility_id:name' => 'public',
        'is_active' => TRUE,
      ],
    ],
  ],
];
