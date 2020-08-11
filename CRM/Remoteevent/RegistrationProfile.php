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
     * @return array field specs
     *   format is field_key => [
     *      'type'       => (string, integer, ..) ,
     *      'validation' => (string, integer, email),
     *   ]
     */
    abstract public function getFields();


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
            case 'email':
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
        $profiles = self::getAvailableRegistrationProfiles();
        if (isset($profiles[$profile_name])) {
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
                    'option.limit' => 0,
                    'option_group_id' => 'remote_registration_profiles',
                    'is_active' => 1
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
}
