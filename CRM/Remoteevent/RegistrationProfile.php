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

use CRM_Remoteevent_ExtensionUtil as E;

use Civi\RemoteEvent as RemoteEvent;
use Civi\RemoteParticipant\Event\GetParticipantFormEventBase as GetParticipantFormEventBase;
use Civi\RemoteParticipant\Event\ValidateEvent as ValidateEvent;
use Civi\RemoteParticipant\Event\RegistrationEvent as RegistrationEvent;
use Civi\RemoteEvent\Event\RegistrationProfileListEvent;


/**
 * Abstract base to all registration profile implementations
 */
abstract class CRM_Remoteevent_RegistrationProfile
{
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
    public function getLabel()
    {
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
     * @return array field specs
     *   format is field_key => [
     *      'name'        => field_key
     *      'type'        => field type, one of 'Text', 'Textarea', 'Select', 'Multi-Select', 'Checkbox', 'Date'
     *      'weight'      => int,
     *      'options'     => [value => label (localised)] list  (optional)
     *      'required'    => 0/1
     *      'label'       => field label (localised)
     *      'description' => field description (localised)
     *      'parent'      => can link to parent element, which should be of type 'fieldset'
     *      'value'       => (optional) pre-filled value, typically set in a second pass (addDefaultValues, see below)
     *      'maxlength'   => (optional) max length of the field content (as a string)
     *      'validation'  => content validation, see CRM_Utils_Type strings, but also custom ones like 'Email'
     *                       NOTE: this is just for the optional 'inline' validation in the form,
     *                             the main validation will go through the RemoteParticipant.validate function
     *      'confirm_required' => bool If true, the same value has to be entered again for confirmation. This flag is
     *                            handled by the frontend, so the data received by this extension is unaffected.
     *                            (false, if not set.)
     *   ]
     */
    abstract public function getFields($locale = null);

    public function getAdditionalParticipantsFields(array $event, ?int $maxParticipants = NULL, ?string $locale = NULL): ?array {
      if (!empty($event['is_multiple_registrations'])) {
        $fields = [];
        if (!isset($maxParticipants)) {
          $maxParticipants = $event['max_additional_participants'];
        }
        else {
          $maxParticipants = min($maxParticipants, $event['max_additional_participants']);
        }
        $additional_participants_profile = CRM_Remoteevent_RegistrationProfile::getRegistrationProfile(
          $event['event_remote_registration.remote_registration_additional_participants_profile']
        );
        $additional_fields = $additional_participants_profile->getFields($locale);
        $fields['additional_participants'] = [
          'type' => 'fieldset',
          'name' => 'additional_participants',
          'label' => E::ts('Additional Participants'),
          'weight' => 1000,
          'description' => E::ts('Register up to %1 additional participants', [1 => $event['max_additional_participants']]),
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
            $additional_field['name'] = 'additional_' . $i . '_' . $additional_field['name'];
            $additional_field['parent'] = empty($additional_field['parent']) ? 'additional_' . $i : 'additional_' . $i . '_' . $additional_field['parent'];
            $fields['additional_' . $i . '_' . $additional_field_name] = $additional_field;
          }
        }
      }
      return $fields ?? NULL;
    }

    /**
     * Add the default values to the form data, so people using this profile
     *  don't have to enter everything themselves
     *
     * @param GetParticipantFormEventBase $resultsEvent
     *   the locale to use, defaults to null none. Use 'default' for current
     *
     */
    abstract public function addDefaultValues(GetParticipantFormEventBase $resultsEvent);

    /**
     * Validate the profile fields individually.
     * This only validates the mere data types,
     *   more complex validation (e.g. over multiple fields)
     *   have to be performed by the profile implementations
     *
     * @param ValidateEvent $validationEvent
     *      event triggered by the RemoteParticipant.validate or submit API call
     */
    public function validateSubmission($validationEvent)
    {
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
            && ($excessParticipants = CRM_Remoteevent_Registration::getRegistrationCount($event['id']) + 1 + $additionalParticipantsCount - $event['max_participants']) > 0
        ) {
            if (
              !empty($event['has_waitlist'])
              && !empty($event['event_remote_registration.remote_registration_additional_participants_waitlist'])
            ) {
                $validationEvent->addWarning(
                    $l10n->localise('Not enough vacancies for the number of requested participants.')
                    . ' '
                    . $l10n->localise('%1 participant(s) will be added to the waiting list.', [1 => $excessParticipants])
                );
            }
            else {
                $validationEvent->addValidationError('', $l10n->localise('Not enough vacancies for the number of requested participants.'));
            }
        }

        // Validate field values.
        $fields = $this->getFields()
          + $this->getAdditionalParticipantsFields($event, $additionalParticipantsCount);
        foreach ($fields as $field_name => $field_spec) {
            $value = CRM_Utils_Array::value($field_name, $data);
            if (!empty($field_spec['required']) && ($value === null || $value === '')) {
                $validationEvent->addValidationError($field_name, $l10n->localise("Required"));
            } else {
                if (!$this->validateFieldValue($field_spec, $value)) {
                    $validationEvent->addValidationError($field_name, $l10n->localise("Invalid Value"));
                }
                if (!$this->validateFieldLength($field_spec, $value)) {
                    $validationEvent->addValidationError($field_name, $l10n->localise("Value too long"));
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
     */
    public function getFieldEntities($field_key)
    {
        // for now, we assume everything is contact, unless it's custom,
        //   in which case we don't know - or more precisely are too lazy to find out.
        if (preg_match('/^custom_/', $field_key)
            || preg_match('/^\w+[.]\w+$/', $field_key)) {
            return ['Contact', 'Participant'];
        } else {
            return ['Contact'];
        }
    }

    /**
     * Give the profile a chance to manipulate the contact data before it's being sent off to
     *   the contact creation/update
     *
     * @param array $contact_data
     *   contact data
     */
    protected function adjustContactData(&$contact_data)
    {
        // this is just a stub. for now.
    }

    /*************************************************************
     *                HELPER / INFRASTRUCTURE                   **
     *************************************************************/

    /**
     * Add the profile data to the get_form results
     *
     * @param RemoteEvent $remote_event
     *      event triggered by the RemoteParticipant.get_form API call
     *
     * @return \CRM_Remoteevent_RegistrationProfile
     *      the profile
     */
    public static function getProfile($remote_event)
    {
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
        if (!in_array($params['profile'], $allowed_profiles)) {
            throw new CiviCRM_API3_Exception(
                E::ts("Profile [%2] cannot be used with RemoteEvent [%1].", [
                    1 => $event['id'],
                    2 => $params['profile']])
            );
        }

        // simply add the fields from the profile
        return CRM_Remoteevent_RegistrationProfile::getRegistrationProfile($params['profile']);
    }

    /**
     * Add the profile data to the get_form results
     *
     * @param GetParticipantFormEventBase $get_form_results
     *      event triggered by the RemoteParticipant.get_form API call
     *
     * @return array|null
     *      returns API error if there is an issue
     */
    public static function addProfileData($get_form_results)
    {
        // simply add the fields from the profile
        $profile = self::getProfile($get_form_results);
        $event = $get_form_results->getEvent();

        // add the fields
        $locale = $get_form_results->getLocale();
        $fields = $profile->getFields($locale) + $profile->getAdditionalParticipantsFields($event, NULL, $locale);
        $get_form_results->addFields($fields);

        // add default values
        $profile->addDefaultValues($get_form_results);

        $name = $profile->getName();
        $label = $profile->getLabel();
        // add profile "field"
        $get_form_results->addFields([
             'profile' => [
                 'name' => 'profile',
                 'type' => 'Value',
                 'value' => $profile->getName(),
                 'label' => $profile->getLabel(),
             ]
        ]);
    }

    /**
     * Validate the profile fields
     *
     * @param ValidateEvent $validationEvent
     *      event triggered by the RemoteParticipant.validate or submit API call
     */
    public static function validateProfileData($validationEvent)
    {
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
     *      the profile instance
     *
     * @throws Exception
     *      if no profile implementation for this name is available
     */
    public static function getRegistrationProfile($profile_name)
    {
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
    public static function getAvailableRegistrationProfiles()
    {
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
     * @param RegistrationProfileListEvent $registration_profile_list_event
     *
     * @return void
     */
    public static function addOptionValueProfiles(
        RegistrationProfileListEvent $registration_profile_list_event)
    {
        // TODO: Do we use API4?
        $profile_data = civicrm_api3(
            'OptionValue',
            'get',
            [
                'option.limit'      => 0,
                'option_group_id'   => 'remote_registration_profiles',
                'is_active'         => 1,
                'check_permissions' => false
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
    public static function setProfileDataInEventData(&$event)
    {
        $profiles = self::getAvailableRegistrationProfiles();

        // set default profile
        if (isset($event['event_remote_registration.remote_registration_default_profile'])) {
            $default_profile_name = $event['event_remote_registration.remote_registration_default_profile'];
            if (isset($profiles[$default_profile_name])) {
                $event['default_profile'] = $default_profile_name;
            } else {
                $event['default_profile'] = '';
            }
            unset($event['event_remote_registration.remote_registration_default_profile']);
        }

        // set enabled profiles
        $enabled_profiles      = $event['event_remote_registration.remote_registration_profiles'];
        $enabled_profile_names = [];
        if (is_array($enabled_profiles)) {
            $enabled_profile_names = array_intersect($enabled_profiles, array_keys($profiles));
        }
        $event['enabled_profiles'] = implode(',', $enabled_profile_names);
        unset($event['event_remote_registration.remote_registration_profiles']);

        // set default UPDATE profile
        if (isset($event['event_remote_registration.remote_registration_default_update_profile'])) {
            $default_profile_name = $event['event_remote_registration.remote_registration_default_update_profile'];
            if (isset($profiles[$default_profile_name])) {
                $event['default_update_profile'] = $default_profile_name;
            } else {
                $event['default_update_profile'] = '';
            }
            unset($event['event_remote_registration.remote_registration_default_update_profile']);
        }

        // set enabled UPDATE profiles
        if (isset($event['event_remote_registration.remote_registration_update_profiles'])) {
            $enabled_profiles      = $event['event_remote_registration.remote_registration_update_profiles'];
            $enabled_profile_names = [];
            if (is_array($enabled_profiles)) {
                $enabled_profile_names = array_intersect($enabled_profiles, array_keys($profiles));
            }
            $event['enabled_update_profiles'] = implode(',', $enabled_profile_names);
            unset($event['event_remote_registration.remote_registration_update_profiles']);
        } else {
            $event['enabled_update_profiles'] = [];
        }


        // also map remote_registration_enabled
        $event['remote_registration_enabled'] = $event['event_remote_registration.remote_registration_enabled'];
        unset($event['event_remote_registration.remote_registration_enabled']);
    }

    /**
     * Use profile data to identify the contact via XCM
     *
     * @param array $data
     *      Input data
     *
     * @param RegistrationEvent $registration
     *      registration data
     *
     * @return integer
     *      CiviCRM contact ID
     *
     * @throws Exception
     *      If not enough information is provided
     */
    public static function addProfileContactData($registration)
    {
        // get the profile (has already been validated)
        $profile = CRM_Remoteevent_RegistrationProfile::getProfile($registration);

        // then simply add all fields from the profile
        $contact_data = $registration->getContactData();
        $submission_data = $registration->getSubmission();
        foreach ($profile->getFields() as $field_key => $field_spec) {
            if (isset($submission_data[$field_key])) {
                $contact_data[$field_key] = $submission_data[$field_key];
            }
        }

        // give the profile a chance to adjust contact data
        $profile->adjustContactData($contact_data);

        // finally, set the result
        $registration->setContactData($contact_data);
    }


    /**
     * Does this profile have a dedicated XCM profile?
     *
     * @return string|null
     *   XCM profile name
     */
    public function getXCMProfile()
    {
        return null;
    }


    /**
     * Validate the data provided to the profile's fields.
     *  All data beyond the specified fields will be filtered
     *
     * @param array $data
     *   input data
     *
     * @param string mode
     *   'exception' (default) throws an exception if some of the data is invalid,
     *   'filter' will simply drop those
     *
     * @return array
     *   filtered data
     *
     * @throws Exception
     *   if mode=exception and at least one value validates
     */
    public function validateData($data, $mode = 'exception')
    {
        $fields        = $this->getFields();
        $return_values = [];

        foreach ($fields as $field_key => $field_spec) {
            if (isset($data[$field_key])) {
                $value = $data[$field_key];
                if ($this->validateFieldValue($field_spec, $value)) {
                    $return_values[$field_key] = $value;
                } else {
                    if ($mode == 'exception') {
                        throw new Exception(
                            E::ts(
                                "Value '%1' for field %2 is not valid",
                                [
                                    1 => $value,
                                    2 => $field_key
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
    protected function validateFieldValue($field_spec, $value)
    {
        $validation = CRM_Utils_Array::value('validation', $field_spec, '');
        switch ($validation) {
            case 'Email':
                return preg_match('#^([a-zA-Z0-9_\-.]+)@([a-zA-Z0-9_\-.]+)\.([a-zA-Z]{2,5})$#', $value);

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
                    return true;
                } catch (Exception $ex) {
                    return false;
                }

            default:
                // check for regex
                if (substr($validation, 0, 6) == 'regex:') {
                    if (strlen($value) > 0) {
                        return preg_match(substr($validation, 6), $value);
                    } else {
                        return true;
                    }
                }

                // else: no (valid) type given
                return true;
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
    protected function validateFieldLength($field_spec, $value)
    {
        $max_length = (int) CRM_Utils_Array::value('maxlength', $field_spec, 0);
        if ($max_length) {
            // there is a defined max_length -> test it
            if (!is_array($value) && !is_object($value)) {
                return strlen((string) $value) <= $max_length;
            }
        }
        return true;
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
    public static function formatFieldValue($field_spec, $value) {
        switch ($field_spec['validation']) {
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
                    $value = date('YmdHiS', strtotime($value));
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
     * @param GetParticipantFormEventBase $resultsEvent
     *   the locale to use, defaults to null none. Use 'default' for current
     *
     * @param array $contact_fields
     *   list of contact fields
     *
     * @param array $attribute_mapping
     *   maps the contact fields to the profile fields
     *
     */
    public function addDefaultContactValues(GetParticipantFormEventBase $resultsEvent, $contact_fields, $attribute_mapping = [])
    {
        $contact_id = $resultsEvent->getContactID();
        if ($contact_id) {
            // set contact data
            try {
                $contact_data = civicrm_api3('Contact', 'getsingle', [
                    'contact_id' => $contact_id,
                    'return'     => implode(',', $contact_fields),
                ]);
                foreach ($contact_fields as $contact_field) {
                    if (isset($attribute_mapping[$contact_field])) {
                        $profile_field = $attribute_mapping[$contact_field];
                    } else {
                        $profile_field = $contact_field;
                    }
                    $resultsEvent->setPrefillValue($profile_field, $contact_data[$contact_field]);
                }
            } catch (CiviCRM_API3_Exception $ex) {
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
    public function getOptions($option_group_id, $locale, $params = [], $use_name = false, $sort = 'weight asc')
    {
        return CRM_Remoteevent_Tools::getOptions($option_group_id, $locale, $params, $use_name, $sort);
    }

    /**
     * Get a localised list of (enabled) countries
     *
     * @return array list of key => (localised) label
     */
    public function getCountries($locale)
    {
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
            $country_list[$country['id']] = $l10n->localise($country['name'], ['context' => 'country']);
        }

        return $country_list;
    }


    /**
     * Get a localised list of (enabled) states/provinces
     *
     * @return array list of key => (localised) label
     */
    public function getStateProvinces($locale)
    {
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
            $province_list[$province_key] = $l10n->localise($province['name'], ['context' => 'province']);
        }

        return $province_list;
    }


}
