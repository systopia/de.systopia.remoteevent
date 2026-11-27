<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
  [
    'name' => 'OptionValue_remote_event_user',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_contact_roles',
        'label' => E::ts('RemoteEvent User'),
        'name' => 'remote-event-user',
        'description' => E::ts('Contact is allowed to self-register for remote events'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionValue_remote_event_participant',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_contact_roles',
        'label' => E::ts('RemoteEvent Participant'),
        'name' => 'remote-event-participant',
        'description' => E::ts('Contact is registered to a remote event'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionValue_remote_event_coordinator',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_contact_roles',
        'label' => E::ts('RemoteEvent Coordinator'),
        'name' => 'remote-event-coordinator',
        'description' => E::ts('Contact is a remote event coordinator'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionValue_remote_event_participant_coordinator',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_contact_roles',
        'label' => E::ts('RemoteEvent Participant Coordinator'),
        'name' => 'remote-event-participant-coordinator',
        'description' => 'Contact can edit other participant\'s registration data',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
];
