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
 * Form controller for event online registration settings
 */
class CRM_Remoteevent_Form_RegistrationConfig extends CRM_Event_Form_ManageEvent
{
    const NATIVE_ATTRIBUTES_USED = [
        'registration_start_date',
        'registration_end_date',
        'requires_approval',
        'allow_selfcancelxfer',
        'selfcancelxfer_time',
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
    public function preProcess()
    {
        parent::preProcess();
        $this->setSelectedChild('registrationconfig');
    }

    public function buildQuickForm()
    {
        // gather data
        $available_registration_profiles = CRM_Remoteevent_RegistrationProfile::getAvailableRegistrationProfiles();
        $intro_attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'intro_text') + ['class' => 'collapsed', 'preset' => 'civievent'];
        $event_attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');

        // add form elements
        $this->add(
            'checkbox',
            'remote_registration_enabled',
            E::ts("Remote Event Features Enabled?")
        );
        $this->add(
            'select',
            'remote_registration_default_profile',
            E::ts("Default Registration Profile"),
            $available_registration_profiles,
            false,
            ['class' => 'crm-select2']
        );
        $this->add(
            'select',
            'remote_registration_profiles',
            E::ts("Allowed Registration Profiles"),
            $available_registration_profiles,
            false,
            ['class' => 'crm-select2', 'multiple' => 'multiple']
        );
        $this->add(
            'select',
            'remote_registration_xcm_profile',
            E::ts("Registration Contact Matching (XCM)"),
            $this->getAvailableXcmProfiles(false),
            false,
            ['class' => 'crm-select2']
        );
        $this->add(
            'select',
            'remote_registration_default_update_profile',
            E::ts("Default Registration Profile for Updates"),
            $available_registration_profiles,
            false,
            ['class' => 'crm-select2']
        );
        $this->add(
            'select',
            'remote_registration_update_profiles',
            E::ts("Allowed Registration Profiles for Updates"),
            $available_registration_profiles,
            false,
            ['class' => 'crm-select2', 'multiple' => 'multiple']
        );
        $this->add(
            'select',
            'remote_registration_update_xcm_profile',
            E::ts("Update Contact Matching (XCM)"),
            $this->getAvailableXcmProfiles(true),
            false,
            ['class' => 'crm-select2']
        );
        $this->add(
            'checkbox',
            'remote_use_custom_event_location',
            E::ts("Use Custom Event Location?")
        );
        $this->add(
            'text',
            'remote_registration_external_identifier',
            E::ts("External Identifier"),
            ['class' => 'huge'],
            false
        );
        $this->add(
            'checkbox',
            'remote_disable_civicrm_registration',
            E::ts("Disable native CiviCRM Online Registration")
        );
        $this->add(
            'checkbox',
            'remote_registration_suspended',
            E::ts("Registration Suspended?")
        );

        $this->assign('profiles', $available_registration_profiles);

        // add GTAC field
        $this->add('wysiwyg', 'remote_registration_gtac', E::ts('Terms and Conditions'), $intro_attributes);

        // add the fields that we share with the core data structure (copied from CRM_Event_Form_ManageEvent_Registration)
        $this->add('datepicker', 'registration_start_date', ts('Registration Start Date'), [], FALSE, ['time' => TRUE]);
        $this->add('datepicker', 'registration_end_date', ts('Registration End Date'), [], FALSE, ['time' => TRUE]);
        $this->addElement('checkbox', 'requires_approval', ts('Require participant approval?'), NULL);
        $this->addField('allow_selfcancelxfer', ['label' => ts('Allow self-service cancellation or transfer?'), 'type' => 'advcheckbox']);
        $this->add('text', 'selfcancelxfer_time', ts('Cancellation or transfer time limit (hours)'));
        $this->addRule('selfcancelxfer_time', ts('Please enter the number of hours (as an integer).'), 'integer');

        // add custom texts on the various forms
        $this->add('wysiwyg', 'intro_text',E::ts('Event Information'), $intro_attributes);
        $this->add('wysiwyg', 'footer_text',E::ts('Event Information Footer'), $intro_attributes);
        $this->add('text', 'confirm_title',E::ts('Registration Confirmation Title'), $event_attributes['confirm_title']);
        $this->add('wysiwyg', 'confirm_text',E::ts('Registration Confirmation Text'), $event_attributes['confirm_text'] + ['class' => 'collapsed', 'preset' => 'civievent']);
        $this->add('wysiwyg', 'confirm_footer_text',E::ts('Registration Confirmation Footer'), $event_attributes['confirm_text'] + ['class' => 'collapsed', 'preset' => 'civievent']);
        $this->add('text', 'thankyou_title',E::ts('Registration Thank You Title'), $event_attributes['thankyou_title']);
        $this->add('wysiwyg', 'thankyou_text',E::ts('Registration Thank You Text'), $event_attributes['thankyou_text'] + ['class' => 'collapsed', 'preset' => 'civievent']);
        $this->add('wysiwyg', 'thankyou_footer_text',E::ts('Registration Thank You Footer'), $event_attributes['thankyou_text'] + ['class' => 'collapsed', 'preset' => 'civievent']);

        // load and set defaults
        if ($this->_id) {
            $field_list = [
                'event_remote_registration.remote_registration_enabled'                => 'remote_registration_enabled',
                'event_remote_registration.remote_registration_default_profile'        => 'remote_registration_default_profile',
                'event_remote_registration.remote_registration_profiles'               => 'remote_registration_profiles',
                'event_remote_registration.remote_registration_default_update_profile' => 'remote_registration_default_update_profile',
                'event_remote_registration.remote_registration_update_profiles'        => 'remote_registration_update_profiles',
                'event_remote_registration.remote_registration_default_profile_generic'         => 'remote_registration_default_profile',
                'event_remote_registration.remote_registration_profiles_generic'                => 'remote_registration_profiles',
                'event_remote_registration.remote_registration_default_update_profile_generic'  => 'remote_registration_default_update_profile',
                'event_remote_registration.remote_registration_update_profiles_generic'         => 'remote_registration_update_profiles',
                'event_remote_registration.remote_use_custom_event_location'           => 'remote_use_custom_event_location',
                'event_remote_registration.remote_registration_gtac'                   => 'remote_registration_gtac',
                'event_remote_registration.remote_registration_external_identifier'    => 'remote_registration_external_identifier',
                'event_remote_registration.remote_disable_civicrm_registration'        => 'remote_disable_civicrm_registration',
                'event_remote_registration.remote_registration_suspended'              => 'remote_registration_suspended',
                'event_remote_registration.remote_registration_xcm_profile'            => 'remote_registration_xcm_profile',
                'event_remote_registration.remote_registration_update_xcm_profile'     => 'remote_registration_update_xcm_profile',
            ];
            CRM_Remoteevent_CustomData::resolveCustomFields($field_list);
            $values = civicrm_api3(
                'Event',
                'getsingle',
                [
                    'id'     => $this->_id,
                    'return' => implode(',', array_merge(array_keys($field_list), self::NATIVE_ATTRIBUTES_USED)),
                ]
            );

            foreach ($field_list as $custom_key => $form_key) {
                $this->setDefaults([$form_key => CRM_Utils_Array::value($custom_key, $values, '')]);
            }
        }

        $this->addButtons(
            [
                [
                    'type'      => 'submit',
                    'name'      => E::ts('Save'),
                    'isDefault' => true,
                ],
            ]
        );

        parent::buildQuickForm();
    }

    public function validate()
    {
        parent::validate();

        if (!empty($this->_submitValues['remote_registration_enabled'])) {
            // online registration is enabled, do some checks:
            if (empty($this->_submitValues['remote_registration_default_profile'])) {
                $this->_errors['remote_registration_default_profile'] = E::ts("You must select a default profile");
            }
        }

        // make sure the external id is unique
        if (strlen($this->_submitValues['remote_registration_external_identifier']) > 0) {
            // check the format
            if (!preg_match('/^[0-9a-zA-Z_#-]+$/', $this->_submitValues['remote_registration_external_identifier'])) {
                $this->_errors['remote_registration_external_identifier'] =
                    E::ts("The external identifier can only contain basic characters, numbers, and the characters '_', '#', and '-'.");
            }
            else {
                // check if it already exists (with another event)
                $exists_query = [
                    'return' => 'id',
                    'event_remote_registration.remote_registration_external_identifier' =>
                        $this->_submitValues['remote_registration_external_identifier'],
                ];
                CRM_Remoteevent_CustomData::resolveCustomFields($exists_query);
                $exists_for_event_id = civicrm_api3('Event', 'get', $exists_query);
                if (!empty($exists_query['id']) && $exists_query['id'] != $this->_id) {
                    $this->_errors['remote_registration_external_identifier'] = E::ts("This external identifier is already in use");
                }
            }
        }

        return (0 == count($this->_errors));
    }

    public function postProcess()
    {
        $values = $this->exportValues();

        // todo: make sure default profile is one of the enabled ones

        // store data
        $event_update = [
            'id'                                                            => $this->_id,
            'is_template'                                                   => CRM_Remoteevent_RemoteEvent::isTemplate($this->_id),
            'event_remote_registration.remote_registration_enabled'         => CRM_Utils_Array::value(
                'remote_registration_enabled',
                $values,
                0
            ),
            'event_remote_registration.remote_invitation_enabled'           => CRM_Utils_Array::value(
                'remote_invitation_enabled',
                $values,
                0
            ),
            'event_remote_registration.remote_use_custom_event_location'    => CRM_Utils_Array::value(
                'remote_use_custom_event_location',
                $values,
                0
            ),
            'event_remote_registration.remote_disable_civicrm_registration'    => CRM_Utils_Array::value(
                'remote_disable_civicrm_registration',
                $values,
                0
            ),
            'event_remote_registration.remote_registration_suspended'    => CRM_Utils_Array::value(
                'remote_registration_suspended',
                $values,
                0
            ),
            'event_remote_registration.remote_registration_default_profile_generic'        => $values['remote_registration_default_profile'],
            'event_remote_registration.remote_registration_update_profiles_generic'        => $values['remote_registration_update_profiles'],
            'event_remote_registration.remote_registration_default_update_profile_generic' => $values['remote_registration_default_update_profile'],
            'event_remote_registration.remote_registration_profiles_generic'               => json_encode($values['remote_registration_profiles']),
            'event_remote_registration.remote_registration_external_identifier'    => $values['remote_registration_external_identifier'],
            'event_remote_registration.remote_registration_gtac'                   => $values['remote_registration_gtac'],
            'event_remote_registration.remote_registration_xcm_profile'            => $values['remote_registration_xcm_profile'],
            'event_remote_registration.remote_registration_update_xcm_profile'     => $values['remote_registration_update_xcm_profile'],
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
            } else {
                $enabled_profiles = [$enabled_profiles];
            }
        }
        if (!in_array($values['remote_registration_default_profile'], $enabled_profiles)) {
            $enabled_profiles[] = $values['remote_registration_default_profile'];
        }
        $event_update['event_remote_registration.remote_registration_profiles_generic'] = CRM_Utils_Array::implodePadded($enabled_profiles);

        // make sure the default UPDATE profile is part of the enabled profiles
        $enabled_profiles = $values['remote_registration_update_profiles'];
        if (!is_array($enabled_profiles)) {
            if (empty($enabled_profiles)) {
                $enabled_profiles = [];
            } else {
                $enabled_profiles = [$enabled_profiles];
            }
        }
        if (!in_array($values['remote_registration_default_update_profile'], $enabled_profiles)) {
            $enabled_profiles[] = $values['remote_registration_default_update_profile'];
        }
        $event_update['event_remote_registration.remote_registration_update_profiles_generic'] = CRM_Utils_Array::implodePadded($enabled_profiles);

        // resolve custom fields
        CRM_Remoteevent_CustomData::resolveCustomFields($event_update);

        // add all the native fields
        foreach (self::NATIVE_ATTRIBUTES_USED as $field_name) {
            $event_update[$field_name] = CRM_Utils_Array::value($field_name, $values, '');
        }

        // write out the changes
        civicrm_api3('Event', 'create', $event_update);

        // this seems to be needed in order to do the right thing
        $this->_action = CRM_Core_Action::UPDATE;

        parent::endPostProcess();
    }

    /**
     * Get a list of the available XCM profiles plus the default option
     *
     * @param bool $can_be_off
     *   if this is true, an 'off' option will be added to prevent XCM to run
     *
     * @return array
     *   list of string(key) => string(label)
     */
    protected function getAvailableXcmProfiles($can_be_off = false)
    {
        $profiles = CRM_Xcm_Configuration::getProfileList();
        $profiles[''] = E::ts("Default (global RemoteEvent settings)");
        if ($can_be_off) {
            $profiles['off'] = E::ts("No Contact Updates");
        }
        return $profiles;
    }


    private function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
