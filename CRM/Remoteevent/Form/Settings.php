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
 * Basic settings page
 */
class CRM_Remoteevent_Form_Settings extends CRM_Core_Form
{
    const SETTINGS = [
        'remote_registration_blocking_status_list',
        'remote_registration_invitation_confirm_default_value',
        'remote_registration_speaker_roles',
        'remote_registration_link',
        'remote_registration_modify_link',
        'remote_registration_cancel_link',
        'remote_registration_xcm_profile',
        'remote_registration_xcm_profile_update',
        'remote_participant_change_activity_type_id',
        'remote_event_get_performance_enhancement',
        'remote_event_get_session_data',
    ];

    public function buildQuickForm()
    {
        $this->setTitle(E::ts("CiviRemote Event - General Configuration"));

        $this->add(
            'select',
            'remote_registration_blocking_status_list',
            E::ts("Statuses blocking (re)registration"),
            $this->getNegativeStatusList(),
            false,
            ['class' => 'crm-select2', 'multiple' => 'multiple']
        );

        $this->add(
            'select',
            'remote_registration_invitation_confirm_default_value',
            E::ts('Default value for confirmation of invitations'),
            [
                0 => E::ts('Decline Invitation'),
                1 => E::ts('Accept Invitation'),
            ],
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'remote_registration_speaker_roles',
            E::ts("Speaker Roles"),
            CRM_Remoteevent_EventCache::getRoles(),
            false,
            ['class' => 'crm-select2', 'multiple' => 'multiple']
        );

        $this->add(
            'select',
            'remote_event_get_session_data',
            E::ts("Submit Session Data"),
            [0 => E::ts("no"), 1 => E::ts("yes")],
            false,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'remote_participant_change_activity_type_id',
            E::ts("Participant Update Activity Type"),
            $this->getActivityTypes(),
            false,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'remote_registration_xcm_profile',
            E::ts("Default Matcher Profile (XCM)"),
            CRM_Xcm_Configuration::getProfileList(),
            false,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'remote_registration_xcm_profile_update',
            E::ts("Default Update Profile (XCM)"),
            ['off' => E::ts("No Updates")] + CRM_Xcm_Configuration::getProfileList(),
            false,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'remote_event_get_performance_enhancement',
            E::ts("Speed up API"),
            [0 => E::ts("no"), 1 => E::ts("yes")],
            false,
            ['class' => 'crm-select2']
        );


//        $this->add(
//            'text',
//            'remote_registration_link',
//            E::ts("Registration Link"),
//            ['class' => 'huge']
//        );
//        $this->addRule('remote_registration_link', E::ts("Please enter a valid URL"), 'url');

        $this->add(
            'text',
            'remote_registration_modify_link',
            E::ts("Registration Modification Link"),
            ['class' => 'huge']
        );
        $this->addRule('remote_registration_modify_link', E::ts("Please enter a valid URL"), 'url');
        $this->addRule(
            'remote_registration_modify_link',
            E::ts('The link must include the placeholder <code>{token}</code>.'),
            'regex',
            '/\{token\}/'
        );

        $this->add(
            'text',
            'remote_registration_cancel_link',
            E::ts("Registration Cancellation Link"),
            ['class' => 'huge']
        );
        $this->addRule('remote_registration_cancel_link', E::ts("Please enter a valid URL"), 'url');
        $this->addRule(
            'remote_registration_modify_link',
            E::ts('The link must include the placeholder <code>{token}</code>.'),
            'regex',
            '/\{token\}/'
        );

        $this->addButtons(
            [
                [
                    'type' => 'submit',
                    'name' => E::ts('Save'),
                    'isDefault' => true,
                ],
            ]
        );

        // add defaults
        foreach (self::SETTINGS as $setting_key) {
            $this->setDefaults([$setting_key => Civi::settings()->get($setting_key)]);
        }

        parent::buildQuickForm();
    }

    public function postProcess()
    {
        $values = $this->exportValues();

        foreach (self::SETTINGS as $setting_key) {
            Civi::settings()->set($setting_key, CRM_Utils_Array::value($setting_key, $values));
        }
        CRM_Core_Session::setStatus(E::ts("Configuration Updated"));
        parent::postProcess();
    }

    /**
     * Get a list of negative registration statuses
     *
     * @return array
     *   status id => status label
     */
    public function getNegativeStatusList() {
        $list = [];
        $query = civicrm_api3('ParticipantStatusType', 'get', [
            'option.limit' => 0,
            'class'        => 'Negative',
            'return'       => 'id,label'
        ]);
        foreach ($query['values'] as $status) {
            $list[$status['id']] = $status['label'];
        }
        return $list;
    }

    /**
     * Get a list of activity types
     */
    private function getActivityTypes()
    {
        $types = ['' => E::ts("-- don't record changes --")];
        $query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'activity_type',
            'is_reserved'     => 0,
            'component_id'    => ['IS NULL' => 1],
            'option.limit'    => 0,
            'return'          => 'label,value'
        ]);
        foreach ($query['values'] as $type) {
            $types[$type['value']] = $type['label'];
        }
        return $types;
    }
}
