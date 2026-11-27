<?php
use CRM_Remoteevent_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_remote_registration_profiles',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'remote_registration_profiles',
        'title' => E::ts('Remote Registration Profiles'),
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
    'name' => 'OptionGroup_remote_registration_profiles_OptionValue_Standard1',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_registration_profiles',
        'label' => E::ts('Email only'),
        'name' => 'Standard1',
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
    'name' => 'OptionGroup_remote_registration_profiles_OptionValue_OneClick',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_registration_profiles',
        'label' => E::ts('One-Click'),
        'name' => 'OneClick',
        'description' => E::ts('Requires the contact to be identified via remote_contact_id rather than provide contact data'),
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
    'name' => 'OptionGroup_remote_registration_profiles_OptionValue_Standard2',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_registration_profiles',
        'label' => E::ts('Email and Name'),
        'name' => 'Standard2',
        'description' => E::ts('Email, prefix, title, first and last name'),
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
    'name' => 'OptionGroup_remote_registration_profiles_OptionValue_Standard3',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_registration_profiles',
        'label' => E::ts('Full (full name/email/address/phone)'),
        'name' => 'Standard3',
        'description' => E::ts('Email, prefix, title, first name, last name, postal address and phone'),
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
    'name' => 'OptionGroup_remote_registration_profiles_OptionValue_Standard4',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'remote_registration_profiles',
        'label' => E::ts('Full + Organisation + Billing'),
        'name' => 'Standard4',
        'description' => E::ts('Email, prefix, title, first name, last name, postal address and phone + organisation details + billing address'),
        'is_reserved' => TRUE,
        'is_active' => FALSE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
];
