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

use \Civi\RemoteEvent\Event\GetRegistrationFormResultsEvent as GetRegistrationFormResultsEvent;


/**
 * Abstract base to all registration profile implementations
 */
abstract class CRM_Remoteevent_RegistrationProfile
{
    /**
     * Get the internal name of the profile represented
     *
     * @return string name
     */
    abstract public function getName();

    /**
     * Get the list of fields expected by this profile
     *
     * @param string $locale
     *   the locale to use, defaults to null none. Use 'default' for current
     *
     * @return array field specs
     *   format is field_key => [
     *      'name'        => field_key
     *      'type'        => field type, one of 'Text', 'Textarea', 'Select', 'Multi-Select', 'Checkbox'
     *      'weight'      => int,
     *      'options'     => [value => label (localised)] list  (optional)
     *      'required'    => 0/1
     *      'label'       => field label (localised)
     *      'description' => field description (localised)
     *      'group_name'  => grouping
     *      'group_label' => group title (localised)
     *      'validation'  => content validation, see CRM_Utils_Type strings, but also custom ones like 'Email'
     *                       NOTE: this is just for the optional 'inline' validation in the form,
     *                             the main validation will go through the RemoteParticipant.validate function
     *   ]
     */
    abstract public function getFields($locale = null);


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
     * Use profile data to identify the contact via XCM
     *
     * @param array $data
     *      Input data
     *
     * @return integer
     *      CiviCRM contact ID
     *
     * @throws Exception
     *      If not enough information is provided
     */
    public function identifyContact($data)
    {
        // base implementation simply pushes all data into XCM:
        $contact_identification = [];
        foreach ($this->getFields() as $field_key => $field_spec) {
            if (isset($data[$field_key])) {
                // todo: validate again?
                $contact_identification[$field_key] = $data[$field_key];
            }
        }

        // add xcm profile, if one given
        $xcm_profile = $this->getXCMProfile();
        if ($xcm_profile) {
            $contact_identification['xcm_profile'] = $xcm_profile;
        }

        // run through the contact matcher
        try {
            $match = civicrm_api3('Contact', 'getorcreate', $contact_identification);
            return $match['contact_id'];
        } catch (Exception $ex) {
            $profile_name = $this->getName();
            throw new Exception(
                E::ts("Data for profile '%1' not enough to identify/create contact.", [1 => $profile_name])
            );
        }
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
        $fields = $this->getFields();
        $return_values = [];

        foreach ($fields as $field_key => $field_spec) {
            if (isset($data[$field_key])) {
                $value = $data[$field_key];
                if ($this->validateFieldValue($field_spec, $value)) {
                    $return_values[$field_key] = $value;
                } else {
                    if ($mode == 'exception') {
                        throw new Exception(E::ts("Value '%1' for field %2 is not valid", [
                            1 => $value,
                            2 => $field_key
                        ]));
                    }
                }
            }
        }

        return $return_values;
    }

    /**
     * Validation the given
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
    protected function validateFieldValue($field_spec, $value) {
        switch ($field_spec['validation']) {
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
                    CRM_Utils_Type::validate($value, $field_spec['validation']);
                    return true;
                } catch (Exception $ex) {
                    return false;
                }

            default: // no type given
                return true;
        }
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
        $profiles = self::getAvailableRegistrationProfiles('name');
        if (in_array($profile_name, $profiles)) {
            // get class
            $class_candidate = "CRM_Remoteevent_RegistrationProfile_{$profile_name}";
            if (class_exists($class_candidate)) {
                return new $class_candidate();
            } else {
                // todo: extend to use Symfony hooks
                throw new Exception(E::ts("Implementation for profile '%1' not found.", [1 => $profile_name]));
            }
        } else {
            throw new Exception(E::ts("Registration profile '%1' is not available (any more).", [1 => $profile_name]));
        }
    }

    /**
     * Add the profile data to the get_registration_form results
     *
     * @param GetRegistrationFormResultsEvent $get_form_results
     *      event triggered by the RemoteEvent.get_registration_form API call
     */
    public static function addProfileData($get_form_results) {
        $params = $get_form_results->getParams();
        $event  = $get_form_results->getEvent();

        if (empty($params['profile'])) {
            // use default profile
            $params['profile'] = $event['default_profile'];
        }
        $allowed_profiles = explode(',', $event['enabled_profiles']);
        if (!in_array($params['profile'], $allowed_profiles)) {
            return civicrm_api3_create_error(
                E::ts("Profile [%2] cannot be used with RemoteEvent [%1].", [
                    1 => $params['event_id'],
                    2 => $params['profile']
                ])
            );
        }

        // get locale
        $locale = CRM_Utils_Array::value('locale', $params, CRM_Core_I18n::getLocale());

        // simply add the fields from the profile
        $profile = CRM_Remoteevent_RegistrationProfile::getRegistrationProfile($params['profile']);
        $get_form_results->addFields($profile->getFields($locale));
    }

        /**
     * Update the profile data in the event info as returned by the API
     * @param array $event
     *    event data, to be manipulated in place
     */
    public static function setProfileDataInEventData(&$event) {
        $profiles = self::getAvailableRegistrationProfiles('name');

        // set default profile
        if (isset($event['event_remote_registration.remote_registration_default_profile'])) {
            $default_profile_id = (int) $event['event_remote_registration.remote_registration_default_profile'];
            if (isset($profiles[$default_profile_id])) {
                $event['default_profile'] = $profiles[$default_profile_id];
            } else {
                $event['default_profile'] = '';
            }
            unset($event['event_remote_registration.remote_registration_default_profile']);
        }

        // enabled profiles
        $enabled_profiles = $event['event_remote_registration.remote_registration_profiles'];
        $enabled_profile_names = [];
        if (is_array($enabled_profiles)) {
            foreach ($enabled_profiles as $profile_id) {
                if (isset($profiles[$profile_id])) {
                    $enabled_profile_names[] = $profiles[$profile_id];
                }
            }
        }
        $event['enabled_profiles'] = implode(',', $enabled_profile_names);
        unset($event['event_remote_registration.remote_registration_profiles']);

        // also map remote_registration_enabled
        $event['remote_registration_enabled'] = $event['event_remote_registration.remote_registration_enabled'];
        unset($event['event_remote_registration.remote_registration_enabled']);
    }

    /**
     * Get a list of all currently available registration profiles
     *
     * @param string $name_field
     *   should the name be the 'label' (default) or the 'name'
     *
     * @return array
     *   profile id => profile name
     */
    public static function getAvailableRegistrationProfiles($name_field = 'label')
    {
        $profile_data = null;
        if ($profile_data === null) {
            $profile_data = [];
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
        }

        // compile response
        $profiles = [];
        foreach ($profile_data['values'] as $profile) {
            $profiles[$profile['value']] = $profile[$name_field];
        }
        return $profiles;
    }

    /**
     * Get a localised list of option group values for the field keys
     *
     * @param string|integer $option_group_id
     *   identifier for the option group
     *
     * @return array list of key => (localised) label
     */
    public function getOptions($option_group_id, $locale, $params = [])
    {
        $option_list = [];
        $query = [
            'option.limit'    => 0,
            'option_group_id' => $option_group_id,
            'return'          => 'value,label',
            'is_active'       => 1,
            'sort'            => 'weight asc',
        ];

        // extend/override query
        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }

        // run query + compile result
        $result = civicrm_api3('OptionValue', 'get', $query);
        $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
        foreach ($result['values'] as $entry) {
            $option_list[$entry['value']] = $l10n->localise($entry['label']);
        }

        return $option_list;
    }

    /**
     * Get a localised list of (enabled) countries
     *
     * @return array list of key => (localised) label
     */
    public function getCountries($locale)
    {
        $country_list = [];
        $country_limit = CRM_Core_BAO_Country::countryLimit();
        $countries = civicrm_api3('Country', 'get', [
            'iso_code'     => ['IN' => $country_limit],
            'option.limit' => 0,
            'return'       => 'id,name',
        ]);
        $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
        foreach ($countries['values'] as $country) {
            $country_list[$country['id']] = $l10n->localise($country['name']);
        }

        return $country_list;
    }
}
