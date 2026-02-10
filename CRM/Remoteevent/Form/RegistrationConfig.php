<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

declare(strict_types = 1);

use Civi\Api4\Event;
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Form controller for event online registration settings
 */
class CRM_Remoteevent_Form_RegistrationConfig extends CRM_Event_Form_ManageEvent {
  private const NATIVE_ATTRIBUTES_USED = [
    'registration_start_date',
    'registration_end_date',
    'requires_approval',
    'allow_selfcancelxfer',
    'selfcancelxfer_time',
    'is_multiple_registrations',
    'max_additional_participants',
    'intro_text',
    'footer_text',
    'confirm_title',
    'confirm_text',
    'confirm_footer_text',
    'thankyou_title',
    'thankyou_text',
    'thankyou_footer_text',
  ];

  /**
   * Set variables up before form is built.
   */
  public function preProcess(): void {
    parent::preProcess();
    $this->setSelectedChild('registrationconfig');
    Civi::resources()->addScriptFile(E::LONG_NAME, 'js/registration-config.js');
  }

  public function buildQuickForm(): void {
    // gather data
    $available_registration_profiles = CRM_Remoteevent_RegistrationProfile::getAvailableRegistrationProfiles();
    $intro_attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'intro_text')
      + [
        'class' => 'collapsed',
        'preset' => 'civievent',
      ];
    $event_attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');

    // add form elements
    $this->add(
        'checkbox',
        'remote_registration_enabled',
        E::ts('Remote Event Features Enabled?')
    );
    $this->add(
        'select',
        'remote_registration_default_profile',
        E::ts('Default Registration Profile'),
        $available_registration_profiles,
        FALSE,
        ['class' => 'crm-select2 required']
    );
    $this->add(
        'select',
        'remote_registration_profiles',
        E::ts('Allowed Registration Profiles'),
        $available_registration_profiles,
        FALSE,
        ['class' => 'crm-select2 required', 'multiple' => 'multiple']
    );
    $this->add(
        'select',
        'remote_registration_xcm_profile',
        E::ts('Registration Contact Matching (XCM)'),
        $this->getAvailableXcmProfiles(FALSE),
        FALSE,
        ['class' => 'crm-select2 required']
    );
    $this->add(
        'select',
        'remote_registration_default_update_profile',
        E::ts('Default Registration Profile for Updates'),
        $available_registration_profiles,
        FALSE,
        ['class' => 'crm-select2 required']
    );
    $this->add(
        'select',
        'remote_registration_update_profiles',
        E::ts('Allowed Registration Profiles for Updates'),
        $available_registration_profiles,
        FALSE,
        ['class' => 'crm-select2', 'multiple' => 'multiple']
    );
    $this->add(
        'checkbox',
        'remote_registration_additional_participants_waitlist',
        E::ts('Allow registering additional participants on waiting list')
    );
    $this->add(
      'select',
      'remote_registration_additional_participants_profile',
      E::ts('Registration Profile for Additional Participants'),
      ['' => E::ts('- Select -')] + $available_registration_profiles,
      FALSE,
      ['class' => 'crm-select2 required']
    );
    $this->add(
      'select',
      'remote_registration_additional_participants_xcm_profile',
      E::ts('Registration Contact Matching for Additional Participants'),
      ['' => E::ts('- Select -')] + $this->getAvailableXcmProfiles(TRUE),
      FALSE,
      ['class' => 'crm-select2 required']
    );
    $this->add(
        'select',
        'remote_registration_update_xcm_profile',
        E::ts('Update Contact Matching (XCM)'),
        $this->getAvailableXcmProfiles(TRUE),
        FALSE,
        ['class' => 'crm-select2 required']
    );
    $this->add(
        'checkbox',
        'require_user_account',
        E::ts('Require user account for registration?')
    );
    $this->add(
        'checkbox',
        'remote_use_custom_event_location',
        E::ts('Use Custom Event Location?')
    );
    $this->add(
        'text',
        'remote_registration_external_identifier',
        E::ts('External Identifier'),
        ['class' => 'huge'],
        FALSE
    );
    $this->add(
        'checkbox',
        'remote_disable_civicrm_registration',
        E::ts('Disable native CiviCRM Online Registration')
    );
    $this->add(
        'checkbox',
        'remote_registration_suspended',
        E::ts('Registration Suspended?')
    );

    $this->add(
      'select',
      'remote_registration_mailing_list_group_ids',
      E::ts('Mailing Lists Available for Subscription'),
      $this->getMailinglistGroups(),
      FALSE,
      ['class' => 'crm-select2', 'multiple' => 'multiple']
    );

    $this->add(
      'checkbox',
      'remote_registration_is_mailing_list_double_optin',
      E::ts('Double Opt-in for Mailing List Subscriptions?')
    );

    $this->add(
      'text',
      'remote_registration_mailing_list_double_optin_subject',
      E::ts('Subject for Double Opt-in Email')
    );

    $this->add(
      'wysiwyg',
      'remote_registration_mailing_list_double_optin_text',
      E::ts('Text for Double Opt-in Email')
    );
    $this->addRule(
      'remote_registration_mailing_list_double_optin_text',
      E::ts('The text must contain the placeholder <code>{subscribe.url}</code>.'),
      'regex',
      '/\{subscribe\.url\}/'
    );

    $this->assign('profiles', $available_registration_profiles);

    // add GTAC field
    $this->add('wysiwyg', 'remote_registration_gtac', E::ts('Terms and Conditions'), $intro_attributes);

    $this->add(
      'text',
      'remote_registration_mailing_list_subscriptions_label',
      E::ts('Label for Mailing List Subscriptions')
    );

    // add the fields that we share with the core data structure (copied from CRM_Event_Form_ManageEvent_Registration)
    $this->add('datepicker', 'registration_start_date', ts('Registration Start Date'), [], FALSE, ['time' => TRUE]);
    $this->add('datepicker', 'registration_end_date', ts('Registration End Date'), [], FALSE, ['time' => TRUE]);
    $this->addElement('checkbox', 'requires_approval', ts('Require participant approval?'), NULL);
    $this->addField(
      'allow_selfcancelxfer',
      ['label' => ts('Allow self-service cancellation or transfer?'), 'type' => 'advcheckbox']
    );
    $this->add('text', 'selfcancelxfer_time', ts('Cancellation or transfer time limit (hours)'));
    $this->addRule('selfcancelxfer_time', ts('Please enter the number of hours (as an integer).'), 'integer');
    $this->addElement('checkbox', 'is_multiple_registrations', E::ts('Register multiple participants?'));
    // CRM-17745: Make maximum additional participants configurable
    $numericOptions = ['' => E::ts('- Select -')] + CRM_Core_SelectValues::getNumericOptions(1, 9);
    $this->add(
      'select',
      'max_additional_participants',
      E::ts('Maximum additional participants'),
      $numericOptions,
      FALSE,
      ['class' => 'crm-select2 required']
    );

    // add custom texts on the various forms
    $this->add('wysiwyg', 'intro_text', E::ts('Event Information'), $intro_attributes);
    $this->add('wysiwyg', 'footer_text', E::ts('Event Information Footer'), $intro_attributes);
    $this->add('text', 'confirm_title', E::ts('Registration Confirmation Title'), $event_attributes['confirm_title']);
    $this->add(
      'wysiwyg',
      'confirm_text',
      E::ts('Registration Confirmation Text'),
      $event_attributes['confirm_text'] + ['class' => 'collapsed', 'preset' => 'civievent']
    );
    $this->add(
      'wysiwyg',
      'confirm_footer_text',
      E::ts('Registration Confirmation Footer'),
      $event_attributes['confirm_text'] + ['class' => 'collapsed', 'preset' => 'civievent']
    );
    $this->add('text', 'thankyou_title', E::ts('Registration Thank You Title'), $event_attributes['thankyou_title']);
    $this->add(
      'wysiwyg',
      'thankyou_text',
      E::ts('Registration Thank You Text'),
      $event_attributes['thankyou_text'] + ['class' => 'collapsed', 'preset' => 'civievent']
    );
    $this->add(
      'wysiwyg',
      'thankyou_footer_text',
      E::ts('Registration Thank You Footer'),
      $event_attributes['thankyou_text'] + ['class' => 'collapsed', 'preset' => 'civievent']
    );

    // load and set defaults
    if ($this->_id) {
      $field_list = [
        // phpcs:disable Generic.Files.LineLength.TooLong
        'event_remote_registration.remote_registration_enabled' => 'remote_registration_enabled',
        'event_remote_registration.remote_registration_default_profile' => 'remote_registration_default_profile',
        'event_remote_registration.remote_registration_profiles' => 'remote_registration_profiles',
        'event_remote_registration.remote_registration_default_update_profile' => 'remote_registration_default_update_profile',
        'event_remote_registration.remote_registration_update_profiles' => 'remote_registration_update_profiles',
        'event_remote_registration.remote_use_custom_event_location' => 'remote_use_custom_event_location',
        'event_remote_registration.remote_registration_gtac' => 'remote_registration_gtac',
        'event_remote_registration.remote_registration_external_identifier' => 'remote_registration_external_identifier',
        'event_remote_registration.remote_disable_civicrm_registration' => 'remote_disable_civicrm_registration',
        'event_remote_registration.remote_registration_suspended' => 'remote_registration_suspended',
        'event_remote_registration.require_user_account' => 'require_user_account',
        'event_remote_registration.remote_registration_xcm_profile' => 'remote_registration_xcm_profile',
        'event_remote_registration.remote_registration_additional_participants_waitlist' => 'remote_registration_additional_participants_waitlist',
        'event_remote_registration.remote_registration_update_xcm_profile' => 'remote_registration_update_xcm_profile',
        'event_remote_registration.remote_registration_additional_participants_profile' => 'remote_registration_additional_participants_profile',
        'event_remote_registration.remote_registration_additional_participants_xcm_profile' => 'remote_registration_additional_participants_xcm_profile',
        'event_remote_registration.mailing_list_group_ids' => 'remote_registration_mailing_list_group_ids',
        'event_remote_registration.is_mailing_list_double_optin' => 'remote_registration_is_mailing_list_double_optin',
        'event_remote_registration.mailing_list_double_optin_subject' => 'remote_registration_mailing_list_double_optin_subject',
        'event_remote_registration.mailing_list_double_optin_text' => 'remote_registration_mailing_list_double_optin_text',
        'event_remote_registration.mailing_list_subscriptions_label' => 'remote_registration_mailing_list_subscriptions_label',
        // phpcs:enable
      ];
      $values = Event::get(FALSE)
        ->addSelect(...array_keys($field_list))
        ->addSelect(...self::NATIVE_ATTRIBUTES_USED)
        ->addWhere('id', '=', $this->_id)
        ->execute()
        ->single();

      foreach ($field_list as $custom_key => $form_key) {
        $this->setDefaults([$form_key => $values[$custom_key] ?? NULL]);
      }
    }

    $this->addButtons(
        [
            [
              'type' => 'submit',
              'name' => E::ts('Save'),
              'isDefault' => TRUE,
            ],
        ]
    );

    parent::buildQuickForm();
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public function validate(): bool {
    parent::validate();

    if (!empty($this->_submitValues['remote_registration_enabled'])) {
      // online registration is enabled, do some checks:
      if (empty($this->_submitValues['remote_registration_default_profile'])) {
        $this->_errors['remote_registration_default_profile'] = E::ts('You must select a default profile');
      }
    }

    // make sure the external id is unique
    if (strlen($this->_submitValues['remote_registration_external_identifier']) > 0) {
      // check the format
      if (!preg_match('/^[0-9a-zA-Z_#-]+$/', $this->_submitValues['remote_registration_external_identifier'])) {
        $this->_errors['remote_registration_external_identifier'] =
          E::ts(
            "The external identifier can only contain basic characters, numbers, and the characters '_', '#', and '-'."
          );
      }
      else {
        // check if it already exists (with another event)
        $existsCheckResult = Event::get(FALSE)
          ->addSelect('id')
          ->addWhere(
            'event_remote_registration.remote_registration_external_identifier',
            '=',
            $this->_submitValues['remote_registration_external_identifier']
          )
          ->execute();
        if ($existsCheckResult->count() !== 0 && $existsCheckResult->single()['id'] !== $this->_id) {
          $this->_errors['remote_registration_external_identifier'] = E::ts(
            'This external identifier is already in use'
          );
        }
      }
    }

    // Validate fields for additional participants.
    if (!empty($this->_submitValues['is_multiple_registrations'])) {
      if (empty($this->_submitValues['remote_registration_additional_participants_profile'])) {
        $this->_errors['remote_registration_additional_participants_profile'] = E::ts(
          'You must select a profile for registering additional participants.'
        );
      }
      if (empty($this->_submitValues['max_additional_participants'])) {
        $this->_errors['max_additional_participants'] = E::ts(
          'You must select how many additional participants may be registered.'
        );
      }
      if (empty($this->_submitValues['remote_registration_additional_participants_xcm_profile'])) {
        $this->_errors['remote_registration_additional_participants_xcm_profile'] = E::ts(
          'You must select an XCM matching profile for registering additional participants.'
        );
      }
    }

    if ($this->getSubmitValue('remote_registration_is_mailing_list_double_optin')) {
      if ($this->getSubmitValue('remote_registration_mailing_list_double_optin_subject') === '') {
        $this->setElementError(
        'remote_registration_mailing_list_double_optin_subject',
        E::ts('This value is required.')
        );
      }
    }

    return (0 === count($this->_errors));
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public function postProcess(): void {
    $values = $this->exportValues();

    // todo: make sure default profile is one of the enabled ones

    // store data
    $event_update = [
      // phpcs:disable Generic.Files.LineLength.TooLong
      'id' => $this->_id,
      'is_template' => CRM_Remoteevent_RemoteEvent::isTemplate($this->_id),
      'event_remote_registration.remote_registration_enabled' => $values['remote_registration_enabled'] ?? 0,
      'event_remote_registration.remote_invitation_enabled' => $values['remote_invitation_enabled'] ?? 0,
      'event_remote_registration.remote_use_custom_event_location' => $values['remote_use_custom_event_location'] ?? 0,
      'event_remote_registration.remote_disable_civicrm_registration' => $values['remote_disable_civicrm_registration'] ?? 0,
      'event_remote_registration.remote_registration_suspended' => $values['remote_registration_suspended'] ?? 0,
      'event_remote_registration.require_user_account' => $values['require_user_account'] ?? 0,
      'event_remote_registration.remote_registration_default_profile' => $values['remote_registration_default_profile'],
      'event_remote_registration.remote_registration_default_update_profile' => $values['remote_registration_default_update_profile'],
      'event_remote_registration.remote_registration_external_identifier' => $values['remote_registration_external_identifier'],
      'event_remote_registration.remote_registration_gtac' => $values['remote_registration_gtac'],
      'event_remote_registration.remote_registration_xcm_profile' => $values['remote_registration_xcm_profile'],
      'event_remote_registration.remote_registration_update_xcm_profile' => $values['remote_registration_update_xcm_profile'],
      'event_remote_registration.remote_registration_additional_participants_waitlist' => $values['remote_registration_additional_participants_waitlist'] ?? 0,
      'event_remote_registration.remote_registration_additional_participants_profile' => $values['remote_registration_additional_participants_profile'],
      'event_remote_registration.remote_registration_additional_participants_xcm_profile' => $values['remote_registration_additional_participants_xcm_profile'],
      'event_remote_registration.mailing_list_group_ids' => $values['remote_registration_mailing_list_group_ids'],
      'event_remote_registration.is_mailing_list_double_optin' => $values['remote_registration_is_mailing_list_double_optin'] ?? FALSE,
      'event_remote_registration.mailing_list_double_optin_subject' => $values['remote_registration_mailing_list_double_optin_subject'],
      'event_remote_registration.mailing_list_double_optin_text' => $values['remote_registration_mailing_list_double_optin_text'],
      'event_remote_registration.mailing_list_subscriptions_label' => $values['remote_registration_mailing_list_subscriptions_label'],
      // phpcs:enable
    ];

    // disable civicrm native online registration
    if (!empty($event_update['event_remote_registration.remote_disable_civicrm_registration'])) {
      $event_update['is_online_registration'] = 0;
    }

    // make sure the default profile is part of the enabled profiles
    $enabled_profiles = $values['remote_registration_profiles'];
    if (!is_array($enabled_profiles)) {
      if (empty($enabled_profiles)) {
        $enabled_profiles = [];
      }
      else {
        $enabled_profiles = [$enabled_profiles];
      }
    }
    if (!in_array($values['remote_registration_default_profile'], $enabled_profiles)) {
      $enabled_profiles[] = $values['remote_registration_default_profile'];
    }
    $event_update['event_remote_registration.remote_registration_profiles'] = $enabled_profiles;

    // make sure the default UPDATE profile is part of the enabled profiles
    $enabled_profiles = $values['remote_registration_update_profiles'];
    if (!is_array($enabled_profiles)) {
      if (empty($enabled_profiles)) {
        $enabled_profiles = [];
      }
      else {
        $enabled_profiles = [$enabled_profiles];
      }
    }
    if (!in_array($values['remote_registration_default_update_profile'], $enabled_profiles)) {
      $enabled_profiles[] = $values['remote_registration_default_update_profile'];
    }
    $event_update['event_remote_registration.remote_registration_update_profiles'] = $enabled_profiles;

    // add all the native fields
    foreach (self::NATIVE_ATTRIBUTES_USED as $field_name) {
      $event_update[$field_name] = $values[$field_name] ?? '';
    }

    // write out the changes
    Event::update(FALSE)
      ->setValues($event_update)
      ->execute();

    // this seems to be needed in order to do the right thing
    $this->_action = CRM_Core_Action::UPDATE;

    parent::endPostProcess();
  }

  /**
   * Get a list of the available XCM profiles plus the default option
   *
   * @param bool $canBeOff
   *   if this is true, an 'off' option will be added to prevent XCM to run
   *
   * @phpstan-return array<string, string>
   *   list of string(key) => string(label)
   */
  protected function getAvailableXcmProfiles(bool $canBeOff = FALSE): array {
    $profiles = CRM_Xcm_Configuration::getProfileList();
    $profiles[''] = E::ts('Default (global CiviRemote Event settings)');
    if ($canBeOff) {
      $profiles['off'] = E::ts('No Contact Updates');
    }
    return $profiles;
  }

  /**
   * @phpstan-return array<int, string>
   *   Mapping of group ID to title.
   *
   * @throws \CRM_Core_Exception
   */
  private function getMailinglistGroups(): array {
    return \Civi\Api4\Group::get(FALSE)
      ->addSelect('id', 'title')
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('group_type:name', '=', 'Mailing List')
      ->addGroupBy('title')
      ->execute()
      ->indexBy('id')
      ->column('title');
  }

}
