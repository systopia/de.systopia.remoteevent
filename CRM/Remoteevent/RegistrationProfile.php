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

use Civi\Api4\Participant;
use Civi\RemoteParticipant\Event\Util\ParticipantFormEventUtil;
use CRM_Remoteevent_ExtensionUtil as E;

use Civi\RemoteParticipant\Event\GetParticipantFormEventBase as GetParticipantFormEventBase;
use Civi\RemoteEvent\Event\RegistrationProfileListEvent;

/**
 * Abstract base to all registration profile implementations
 */
// phpcs:ignore Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Remoteevent_RegistrationProfile {

  /**
   * Get the internal name of the profile represented.
   *
   * This name has to be identical to the corresponding OptionGroupValue
   *
   * @return string name
   */
  abstract public function getName();

  /**
   * Get the human-readable name of the profile represented. By default, the
   * option value label is returned, if registered as option value, else the
   * profile name. Subclasses that aren't registered as option value should
   * override it.
   *
   * @return string label
   */
  public function getLabel() {
    $result = civicrm_api3('OptionValue', 'get', [
      'return' => ['label'],
      'option_group_id' => 'remote_registration_profiles',
      'name' => $this->getName(),
      'sequential' => 1,
    ]);

    return $result['values'][0]['label'] ?? $this->getName();
  }

  /**
   * Get the list of fields expected by this profile
   *
   * @param string $locale
   *   the locale to use, defaults to null none. Use 'default' for current
   *
   * phpcs:disable Generic.Files.LineLength.TooLong
   * @return array field specs
   *   format is field_key => [
   *      'name'        => field_key
   *      'entity_name' => 'Contact' or 'Participant'.
   *      'entity_field_name' => string, field_key if not set.
   *      'type'        => field type, one of 'Text', 'Textarea', 'Select', 'Multi-Select', 'Checkbox', 'Date', 'Datetime', 'File', 'Value', 'fieldset'.
   *                       - 'File' doesn't support default values and is always optional on update.
   *                          If no file is submitted on update, the previous one is kept.
   *                          The 'validation' attribute is ignored. 'max_filesize' can be used to specify
   *                          the maximum allowed file size.
   *                       - 'Value' fields are not displayed and can be used for pre-defined values.
   *                       - 'fieldset' is used to group fields.
   *                       - 'Date' fields should have 'validation' set to 'Date' (required for value formatting).
   *                       - 'Datetime' fields should have 'validation' set to 'Timestamp' (required for value formatting).
   *      'weight'      => int,
   *      'options'     => [value => label (localised)] list  (optional)
   *      'required'    => 0/1
   *      'label'       => field label (localised)
   *      'description' => field description (localised)
   *      'parent'      => can link to parent element, which should be of type 'fieldset'
   *      'value'       => (optional) pre-filled value, typically set in a second pass (addDefaultValues, see below)
   *      'value_callback' => (optional) callable(mixed, array<string, mixed>): mixed Called on submission for value conversion.
   *         The first argument is the value itself, the second one the submission data.
   *      'prefill_value_callback' => (optional) callable(mixed, array<string, mixed>): mixed Called when adding default values.
   *         The first argument is the value itself, the second one the values of the same entity referenced by other fields.
   *      'maxlength'   => (optional) max length of the field content (as a string)
   *      'max_filesize' => (optional) maximum file size in bytes when 'type' is 'File'. (This is only approximately
   *                       because the validation is performed while the content is Base64 encoded.)
   *      'validation'  => content validation, see CRM_Utils_Type strings, but also custom ones like 'Email'
   *                       NOTE: this is just for the optional 'inline' validation in the form,
   *                             the main validation will go through the RemoteParticipant.validate function
   *      'confirm_required' => bool If true, the same value has to be entered again for confirmation. This flag is
   *                            handled by the frontend, so the data received by this extension is unaffected.
   *                            (false, if not set.)
   *   ]
   * phpcs:enable
   */
  abstract public function getFields($locale = NULL);

  public function getAdditionalParticipantsFields(array $event,
    ?int $maxParticipants = NULL,
    ?string $locale = NULL
  ): array {
    $fields = [];
    if (!empty($event['is_multiple_registrations'])) {
      $maxParticipants = min(
      $maxParticipants ?? $event['max_additional_participants'],
      $event['max_additional_participants']
      );
      $additional_participants_profile = CRM_Remoteevent_RegistrationProfile::getRegistrationProfile(
        $event['event_remote_registration.remote_registration_additional_participants_profile']
      );
      $additional_fields = $additional_participants_profile->getFields($locale);
      $fields['additional_participants'] = [
        'type' => 'fieldset',
        'name' => 'additional_participants',
        'label' => E::ts('Additional Participants'),
        'weight' => 1000,
        'description' => E::ts(
          'Register up to %1 additional participants',
          [1 => $event['max_additional_participants']]
        ),
      ];
      for ($i = 1; $i <= $maxParticipants; $i++) {
        $fields['additional_' . $i] = [
          'type' => 'fieldset',
          'name' => 'additional_' . $i,
          'parent' => 'additional_participants',
          'label' => E::ts('Additional Participant %1', [1 => $i]),
          'weight' => 10,
          'description' => E::ts('Registration data for additional participant %1', [1 => $i]),
        ];
        foreach ($additional_fields as $additional_field_name => $additional_field) {
          $additional_field['entity_field_name'] ??= $additional_field['name'];
          $additional_field['name'] = 'additional_' . $i . '_' . $additional_field['name'];
          $additional_field['parent'] = empty($additional_field['parent'])
            ? 'additional_' . $i
            : 'additional_' . $i . '_' . $additional_field['parent'];
          $fields['additional_' . $i . '_' . $additional_field_name] = $additional_field;
        }
      }
    }
    return $fields;
  }

  /**
   * Add the default values to the form data, so people using this profile
   * don't have to enter everything themselves.
   */
  public function addDefaultValues(GetParticipantFormEventBase $resultsEvent) {
    $contact_field_mapping = [];
    $participant_field_mapping = [];
    $participant_value_callbacks = [];

    foreach ($this->getFields() as $field_key => $field_spec) {
      if (in_array($field_spec['type'], ['Value', 'fieldset', 'File'], TRUE)) {
        continue;
      }

      // @phpstan-ignore method.deprecated
      $entity_names = (array) ($field_spec['entity_name'] ?? $this->getFieldEntities($field_key));
      $entity_field_name = $field_spec['entity_field_name'] ?? $field_key;
      if (in_array('Contact', $entity_names, TRUE)) {
        $contact_field_mapping[$entity_field_name] = $field_key;
      }
      if (in_array('Participant', $entity_names, TRUE)) {
        $participant_field_mapping[$entity_field_name] = $field_key;
        if (isset($field_spec['prefill_value_callback'])) {
          $participant_value_callbacks[$field_key] = $field_spec['prefill_value_callback'];
        }
      }
    }

    // @phpstan-ignore method.deprecated
    $this->addDefaultContactValues($resultsEvent, array_keys($contact_field_mapping), $contact_field_mapping);

    if ([] !== $participant_field_mapping && $resultsEvent->getParticipantID() > 0) {
      $participant = Participant::get(FALSE)
        ->setSelect(array_keys($participant_field_mapping))
        ->addWhere('id', '=', $resultsEvent->getParticipantID())
        ->execute()
        ->single();

      ParticipantFormEventUtil::mapToPrefill(
        $participant,
        $participant_field_mapping,
        $resultsEvent,
        $participant_value_callbacks
      );
    }
  }

  /**
   * Validate the profile fields individually.
   * This only validates the mere data types,
   *   more complex validation (e.g. over multiple fields)
   *   have to be performed by the profile implementations
   *
   * @param \Civi\RemoteParticipant\Event\ValidateEvent $validationEvent
   *      event triggered by the RemoteParticipant.validate or submit API call
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public function validateSubmission($validationEvent) {
    $data = $validationEvent->getSubmission();
    $event = $validationEvent->getEvent();
    $additionalParticipantsCount = array_reduce(
      preg_grep('#^additional_([0-9]+)(_|$)#', array_keys($data)),
      function(int $carry, string $item) {
        $currentCount = (int) preg_filter('#^additional_([0-9]+)(.*?)$#', '$1', $item);
        return max($carry, $currentCount);
      },
        0
    );
    $l10n = $validationEvent->getLocalisation();

    // Validate number of participants.
    if (
        !empty($event['max_participants'])
        && ($excessParticipants =
          CRM_Remoteevent_Registration::getRegistrationCount($event['id'])
          + 1 + $additionalParticipantsCount - $event['max_participants'])
        > 0
    ) {
      if (
        !empty($event['has_waitlist'])
            && (
                $additionalParticipantsCount === 0
                || !empty($event['event_remote_registration.remote_registration_additional_participants_waitlist'])
            )
      ) {
        $validationEvent->addWarning(
        $l10n->ts('Not enough vacancies for the number of requested participants.')
        . ' '
        . $l10n->ts('%1 participant(s) will be added to the waiting list.', [1 => $excessParticipants])
        );
      }
      else {
        $validationEvent->addValidationError(
          '',
          $l10n->ts('Not enough vacancies for the number of requested participants.')
        );
      }
    }

    // Validate field values.
    $fields = $this->getFields()
          + $this->getAdditionalParticipantsFields($event, $additionalParticipantsCount);
    foreach ($fields as $field_name => $field_spec) {
      $value = $data[$field_name] ?? NULL;
      if (!empty($field_spec['required']) && ($value === NULL || $value === '') &&
      // Files are always optional on update.
      ($field_spec['type'] !== 'File' || $validationEvent->getContext() !== 'update')) {
        $validationEvent->addValidationError($field_name, $l10n->ts('Required'));
      }
      elseif ($field_spec['type'] === 'File') {
        if ($value === NULL) {
          continue;
        }

        if (!is_array($value) || !is_string($value['filename'] ?? NULL) || $value['filename'] === ''
            // File systems usually allow up to 255 characters.
            || strlen($value['filename']) > 255 || !is_string($value['content'] ?? NULL)
          ) {
          $validationEvent->addValidationError($field_name, $l10n->ts('Invalid value'));
          continue;
        }

        $maxFilesize = (int) ($field_spec['max_filesize'] ?? 0);
        if ($maxFilesize > 0) {
          // The file might need up to 38 % more space through Base64 encoding.
          if (strlen($value['content']) > ceil($maxFilesize * 1.38)) {
            $validationEvent->addValidationError($field_name, $l10n->ts('File too large'));
          }
        }
      }
      else {
        if (!$this->validateFieldValue($field_spec, $value)) {
          $validationEvent->addValidationError($field_name, $l10n->ts('Invalid value'));
        }
        if (!$this->validateFieldLength($field_spec, $value)) {
          $validationEvent->addValidationError($field_name, $l10n->ts('Value too long'));
        }
      }
    }
  }

  /**
   * This function will tell you which entity/entities the given field
   *   will relate to. It would mostly be Contact or Participant (or both)
   *
   * @param string $field_key
   *   the field key as used by this profile
   *
   * @return array
   *   list of entities
   *
   * @deprecated Specify entity name in field spec instead.
   */
  public function getFieldEntities($field_key) {
    // for now, we assume everything is contact, unless it's custom,
    //   in which case we don't know - or more precisely are too lazy to find out.
    if (preg_match('/^custom_/', $field_key)
        || preg_match('/^\w+[.]\w+$/', $field_key)) {
      return ['Contact', 'Participant'];
    }
    else {
      return ['Contact'];
    }
  }

  /**
   * Give the profile a chance to manipulate the contact data before it's being sent off to
   *   the contact creation/update
   *
   * @param array $contact_data
   *   contact data
   *
   * @return void
   */
  protected function adjustContactData(&$contact_data) {
    // this is just a stub. for now.
  }

  /**
   * Give the profile a chance to manipulate the contact data before it's being sent off to
   * the contact creation/update
   *
   * This is a public interface method for adjusting contact data, as self::adjustContactData()
   * has protected visibility.
   *
   * @param array $contact_data
   *
   * @return void
   */
  public function modifyContactData(array &$contact_data): void {
    $this->adjustContactData($contact_data);
  }

  /**
   * Add the profile data to the get_form results
   *
   * @param \Civi\RemoteEvent $remote_event
   *      event triggered by the RemoteParticipant.get_form API call
   *
   * @return \CRM_Remoteevent_RegistrationProfile
   *   the profile
   */
  public static function getProfile($remote_event) {
    $params = $remote_event->getQueryParameters();
    $event  = $remote_event->getEvent();

    // get profile
    switch ($remote_event->getContext()) {
      case 'create':
        if (empty($params['profile'])) {
          // use default profile
          $params['profile'] = $event['default_profile'];
        }
        $allowed_profiles = explode(',', $event['enabled_profiles']);
        break;

      case 'update':
        if (empty($params['profile'])) {
          // use default profile
          $params['profile'] = $event['default_update_profile'];
        }
        $allowed_profiles = explode(',', $event['enabled_update_profiles']);
        break;

      default:
        $allowed_profiles = [];
    }

    // check if valid
    if (!in_array($params['profile'], $allowed_profiles, TRUE)) {
      throw new CRM_Core_Exception(
        E::ts('Profile [%2] cannot be used with RemoteEvent [%1].', [
          1 => $event['id'],
          2 => $params['profile'],
        ])
      );
    }

    // simply add the fields from the profile
    return CRM_Remoteevent_RegistrationProfile::getRegistrationProfile($params['profile']);
  }

  /**
   * Add the profile data to the get_form results
   *
   * @param \Civi\RemoteParticipant\Event\GetParticipantFormEventBase $get_form_results
   *      event triggered by the RemoteParticipant.get_form API call
   */
  public static function addProfileData($get_form_results) {
    // simply add the fields from the profile
    $profile = self::getProfile($get_form_results);
    $event = $get_form_results->getEvent();

    // add the fields
    $locale = $get_form_results->getLocale();
    $fields = $profile->getFields($locale);
    if ('create' === $get_form_results->getContext()) {
      $fields += $profile->getAdditionalParticipantsFields($event, NULL, $locale);
    }
    $get_form_results->addFields($fields);

    // add default values
    $profile->addDefaultValues($get_form_results);

    // add profile "field"
    $get_form_results->addFields([
      'profile' => [
        'name' => 'profile',
        'type' => 'Value',
        'value' => $profile->getName(),
        'label' => $profile->getLabel(),
      ],
    ]);
  }

  /**
   * Validate the profile fields
   *
   * @param \Civi\RemoteParticipant\Event\ValidateEvent $validationEvent
   *      event triggered by the RemoteParticipant.validate or submit API call
   */
  public static function validateProfileData($validationEvent) {
    // simply add the fields from the profile
    $profile = self::getProfile($validationEvent);

    // run the validation
    $profile->validateSubmission($validationEvent);
  }

  /**
   * Get a class instance of the given registration profile
   *
   * @param string $profile_name
   *      name of the profile
   *
   * @return CRM_Remoteevent_RegistrationProfile
   *   the profile instance
   *
   * @throws Exception
   *   If no profile implementation for this name is available.
   */
  public static function getRegistrationProfile($profile_name) {
    $profile_list = new RegistrationProfileListEvent();
    // dispatch Registration Profile Event and try to instantiate a profile class from $profile_name
    Civi::dispatcher()->dispatch(RegistrationProfileListEvent::NAME, $profile_list);

    return $profile_list->getProfileInstance($profile_name);
  }

  /**
   * Get a list of all currently available registration profiles
   *
   * @return array
   *   profile name => profile label
   */
  public static function getAvailableRegistrationProfiles() {
    $remote_event_profiles = new RegistrationProfileListEvent();
    // Collect Profiles via Symfony Event
    Civi::dispatcher()->dispatch(RegistrationProfileListEvent::NAME, $remote_event_profiles);

    $profiles = [];
    foreach ($remote_event_profiles->getProfiles() as $profile) {
      $profiles[$profile->getName()] = $profile->getLabel();
    }
    return $profiles;
  }

  /**
   * @param \Civi\RemoteEvent\Event\RegistrationProfileListEvent $registration_profile_list_event
   *
   * @return void
   */
  public static function addOptionValueProfiles(
        RegistrationProfileListEvent $registration_profile_list_event) {
    // TODO: Do we use API4?
    $profile_data = civicrm_api3(
        'OptionValue',
        'get',
        [
          'option.limit'      => 0,
          'option_group_id'   => 'remote_registration_profiles',
          'is_active'         => 1,
          'check_permissions' => FALSE,
        ]
    );
    foreach ($profile_data['values'] as $profile) {
      $class_name = "CRM_Remoteevent_RegistrationProfile_{$profile['name']}";
      $registration_profile_list_event->addProfile($class_name, $profile['name'], $profile['label']);
    }
  }

  /**
   * Update the profile data in the event info as returned by the API
   * @param array $event
   *    event data, to be manipulated in place
   */
  public static function setProfileDataInEventData(&$event) {
    $profiles = self::getAvailableRegistrationProfiles();

    // set default profile
    if (isset($event['event_remote_registration.remote_registration_default_profile'])) {
      $default_profile_name = $event['event_remote_registration.remote_registration_default_profile'];
      if (isset($profiles[$default_profile_name])) {
        $event['default_profile'] = $default_profile_name;
      }
      else {
        $event['default_profile'] = '';
      }
      unset($event['event_remote_registration.remote_registration_default_profile']);
    }

    // set enabled profiles
    $enabled_profiles = $event['event_remote_registration.remote_registration_profiles'] ?? [];
    $enabled_profile_names = array_intersect($enabled_profiles, array_keys($profiles));
    $event['enabled_profiles'] = implode(',', $enabled_profile_names);
    unset($event['event_remote_registration.remote_registration_profiles']);

    // set default UPDATE profile
    if (isset($event['event_remote_registration.remote_registration_default_update_profile'])) {
      $default_profile_name = $event['event_remote_registration.remote_registration_default_update_profile'];
      if (isset($profiles[$default_profile_name])) {
        $event['default_update_profile'] = $default_profile_name;
      }
      else {
        $event['default_update_profile'] = '';
      }
      unset($event['event_remote_registration.remote_registration_default_update_profile']);
    }

    // set enabled UPDATE profiles
    if (isset($event['event_remote_registration.remote_registration_update_profiles'])) {
      $enabled_profiles = $event['event_remote_registration.remote_registration_update_profiles'] ?? [];
      $enabled_profile_names = array_intersect($enabled_profiles, array_keys($profiles));
      $event['enabled_update_profiles'] = implode(',', $enabled_profile_names);
      unset($event['event_remote_registration.remote_registration_update_profiles']);
    }
    else {
      $event['enabled_update_profiles'] = [];
    }

    // also map remote_registration_enabled
    $event['remote_registration_enabled'] = $event['event_remote_registration.remote_registration_enabled'];
    unset($event['event_remote_registration.remote_registration_enabled']);
  }

  /**
   * Does this profile have a dedicated XCM profile?
   *
   * @return string|null
   *   XCM profile name
   */
  public function getXCMProfile() {
    return NULL;
  }

  /**
   * Validate the data provided to the profile's fields.
   *  All data beyond the specified fields will be filtered
   *
   * @param array $data
   *   input data
   *
   * @param string $mode
   *   'exception' (default) throws an exception if some of the data is invalid,
   *   'filter' will simply drop those
   *
   * @return array
   *   filtered data
   *
   * @throws \Exception
   *   If mode=exception and at least one value validates.
   */
  public function validateData($data, $mode = 'exception') {
    $fields        = $this->getFields();
    $return_values = [];

    foreach ($fields as $field_key => $field_spec) {
      if (isset($data[$field_key])) {
        $value = $data[$field_key];
        if ($this->validateFieldValue($field_spec, $value)) {
          $return_values[$field_key] = $value;
        }
        else {
          if ($mode == 'exception') {
            throw new Exception(
            E::ts(
            "Value '%1' for field %2 is not valid",
            [
              1 => $value,
              2 => $field_key,
            ]
            )
            );
          }
        }
      }
    }

    return $return_values;
  }

  /**
   * Validation the given value
   *
   * @phpstan-param array<string, array<string, mixed>> $field_spec
   *    specs, see getFields()
   *
   * @param string|null $value
   *    field value
   *
   * @return boolean
   *   is the value valid
   *
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  protected function validateFieldValue(array $field_spec, $value): bool {
    /** @var string $validation */
    $validation = $field_spec['validation'] ?? '';
    switch ($validation) {
      case 'Email':
        // Since CiviCRM Core uses \CRM_Utils_Rule::email() for validating e-mail addresses, we rely on it here.
        // \CRM_Utils_Rule::email() returns TRUE for unset or empty values, therefore require a value here.
        return NULL !== $value && '' !== $value && \CRM_Utils_Rule::email($value);

      case 'Integer':
      case 'Int':
      case 'Positive':
      case 'CommaSeparatedIntegers':
      case 'Boolean':
      case 'Float':
      case 'Text':
      case 'String':
      case 'Link':
      case 'Date':
      case 'Timestamp':
      case 'Json':
      case 'Alphanumeric':
        try {
          CRM_Utils_Type::validate($value, $validation);
          return TRUE;
        }
        catch (Exception $ex) {
          return FALSE;
        }

      default:
        // check for regex
        if (substr($validation, 0, 6) == 'regex:') {
          if (NULL !== $value && strlen($value) > 0) {
            return preg_match(substr($validation, 6), $value) > 0;
          }
          else {
            return TRUE;
          }
        }

        // else: no (valid) type given
        return TRUE;
    }
  }

  /**
   * Check whether the given value exceeds the length limits (if any defined)
   *
   * @param array $field_spec
   *    specs, see getFields()
   *
   * @param string $value
   *    field value
   *
   * @return boolean
   *   is the value valid
   *
   */
  protected function validateFieldLength($field_spec, $value) {
    $max_length = (int) ($field_spec['maxlength'] ?? 0);
    if ($max_length) {
      // there is a defined max_length -> test it
      if (!is_array($value) && !is_object($value)) {
        return strlen((string) $value) <= $max_length;
      }
    }
    return TRUE;
  }

  /**
   * Format the given field value
   *
   * @param array $field_spec
   *    specs, see getFields()
   *
   * @param string $value
   *    field value
   *
   * @return string
   *   the formatted value
   *
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public static function formatFieldValue($field_spec, $value) {
    switch ($field_spec['validation'] ?? NULL) {
      case 'Integer':
      case 'Int':
      case 'Positive':
        $value = (int) $value;
        break;

      case 'Date':
        if ($value) {
          $value = date('Ymd', strtotime($value));
        }
        break;

      case 'Timestamp':
        if ($value) {
          $value = date('YmdHis', strtotime($value));
        }
        break;

      case 'Boolean':
        $value = empty($value) ? 0 : 1;
        break;

      case 'Float':
        $value = (float) $value;
        break;

      default:
      case 'CommaSeparatedIntegers':
      case 'Text':
      case 'String':
      case 'Link':
      case 'Json':
      case 'Email':
      case 'Alphanumeric':
        // no formatting
        break;
    }
    return $value;
  }

  /**
   * Will set the default values for the given contact fields
   *
   * @phpstan-param array<string> $contact_fields
   *   list of contact fields
   *
   * @phpstan-param array<string, string> $attribute_mapping
   *   maps the contact fields to the profile fields
   *
   * @deprecated Overwrite addDefaultValues() if necessary.
   */
  public function addDefaultContactValues(GetParticipantFormEventBase $resultsEvent,
    $contact_fields,
    $attribute_mapping = []
  ) {
    $contact_id = $resultsEvent->getContactID();
    if ($contact_id) {
      $value_callbacks = [];
      $profile = self::getProfile($resultsEvent);
      $fields = $profile->getFields();
      // set contact data
      $contact_fields = array_combine($contact_fields, $contact_fields);
      // Assume that entity field name and profile field name are equal, if no mapping defined.
      $attribute_mapping += $contact_fields;
      foreach ($attribute_mapping as $field_key) {
        if (isset($fields[$field_key]['prefill_value_callback'])) {
          $value_callbacks[$field_key] = $fields[$field_key]['prefill_value_callback'];
        }
      }

      // TODO: $legacy_contact_fields and $legacy_contact_data include
      //       APIv3-formatted custom fields (custom_x) which might be
      //       expected by other extensions. Those should be removed for
      //       the next major version (2.0).
      $legacy_contact_fields = $contact_fields;
      CRM_Remoteevent_CustomData::resolveCustomFields($legacy_contact_fields);
      $contact_fields += $legacy_contact_fields;
      if (isset($contact_fields['country_id'])) {
        // country_id is only returned if country is selected.
        $contact_fields['country'] = 'country';
      }
      if (isset($contact_fields['state_province_id']) || isset($contact_fields['state_province_name'])) {
        // state_province_id and state_province_name are only returned if state_province is selected
        $contact_fields['state_province'] = 'state_province';
      }
      try {
        $contact_data = \Civi\Api4\Contact::get(FALSE)
          ->setSelect(array_keys($contact_fields))
          ->addWhere('id', '=', $contact_id)
          ->execute()
          ->single();
        $legacy_contact_data = civicrm_api3('Contact', 'getsingle', [
          'contact_id' => $contact_id,
          'return'     => implode(',', array_keys($legacy_contact_fields)),
        ]);
        $contact_data += $legacy_contact_data;
        ParticipantFormEventUtil::mapToPrefill($contact_data, $attribute_mapping, $resultsEvent, $value_callbacks);
      }
      catch (CRM_Core_Exception $ex) {
        // @ignoreException
        // there is no (unique) primary email
      }
    }
  }

  // =============== DATA HELPERS =================

  /**
   * Get a localised list of option group values for the field keys
   *
   * @param string|integer $option_group_id
   *   identifier for the option group
   *
   * @return array list of key => (localised) label
   */
  public function getOptions($option_group_id, $locale, $params = [], $use_name = FALSE, $sort = 'weight asc') {
    return CRM_Remoteevent_Tools::getOptions($option_group_id, $locale, $params, $use_name, $sort);
  }

  /**
   * Get a localised list of (enabled) countries
   *
   * @return array list of key => (localised) label
   */
  public function getCountries($locale) {
    // TODO: Cache localised list of countries (by locale).
    $country_list  = [];
    $country_query = [
      'option.limit' => 0,
      'return'       => 'id,name',
    ];

    // apply country limit
    $country_limit = CRM_Core_BAO_Country::countryLimit();
    if (!empty($country_limit)) {
      $country_query['iso_code'] = ['IN' => $country_limit];
    }
    $countries = civicrm_api3('Country', 'get', $country_query);
    $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
    foreach ($countries['values'] as $country) {
      $country_list[$country['id']] = $l10n->ts($country['name'], ['context' => 'country']);
    }

    return $country_list;
  }

  /**
   * Get a localised list of (enabled) states/provinces
   *
   * @return array list of key => (localised) label
   */
  public function getStateProvinces($locale) {
    // TODO: Cache localised list of provinces (by locale).
    $province_list  = [];
    $province_query = [
      'option.limit' => 0,
      'return'       => 'id,name,country_id',
    ];

    // apply country limit
    $province_limit = CRM_Core_BAO_Country::provinceLimit();
    if (!empty($province_limit)) {
      // country limit is, for whatever reason, in ISO shorts,
      //  so we have to resolve to country IDs first
      $province_limit_country_ids = [];
      $province_country_query = civicrm_api3('Country', 'get', [
        'option.limit' => 0,
        'iso_code'     => ['IN' => $province_limit],
        'return'       => 'id',
      ]);
      foreach ($province_country_query['values'] as $country) {
        $province_limit_country_ids[] = $country['id'];
      }

      // finally: add the parameters
      $province_query['country_id'] = ['IN' => $province_limit_country_ids];
    }

    $provinces = civicrm_api3('StateProvince', 'get', $province_query);
    $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
    foreach ($provinces['values'] as $province) {
      $province_key = "{$province['country_id']}-{$province['id']}";
      $province_list[$province_key] = $l10n->ts($province['name'], ['context' => 'province']);
    }

    return $province_list;
  }

}
