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
 * Collection of upgrade steps.
 */
class CRM_Remoteevent_Upgrader extends CRM_Remoteevent_Upgrader_Base
{

    /**
     * Create the required custom data
     */
    public function install()
    {
        // install remote event stuff
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/option_group_remote_registration_profiles.json'));
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        $customData->syncCustomGroup(E::path('resources/custom_group_alternative_location.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_remote_contact_roles.json'));

        // install sessions
        $this->executeSqlFile('sql/session_install.sql');
        $customData->syncOptionGroup(E::path('resources/option_group_session_slot.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_session_type.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_session_category.json'));

        // add participant status
        $this->addParticipantStatus('Invited', E::ts('Invited'), 'Waiting');
    }

    /**
     * Adding more fields and a 'OneClick' profile
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0004()
    {
        $this->ctx->log->info('Updating data structures');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/option_group_remote_registration_profiles.json'));
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        $customData->syncCustomGroup(E::path('resources/custom_group_alternative_location.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_remote_contact_roles.json'));
        return true;
    }

    /**
     * Adding participant status invited
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0005()
    {
        $this->ctx->log->info('Updating data structures');
        $this->addParticipantStatus('Invited', E::ts('Invited'), 'Waiting');
        return true;
    }

    /**
     * Adding GTAC field
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0006()
    {
        $this->ctx->log->info('Updating data structures');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        return true;
    }

    /**
     * Added Sessions (Workshops)
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0007()
    {
        $this->ctx->log->info('Installing Sessions');
        $this->executeSqlFile('sql/session_install.sql');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/option_group_session_slot.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_session_type.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_session_category.json'));
        return true;
    }


    /**
     * Make sure the given participant status exists
     *
     * @param string $name
     *   the name of the status
     * @param string $label
     *   localised name/label of the status
     * @param string $class
     *   status class
     *
     * @throws \CiviCRM_API3_Exception
     */
    protected function addParticipantStatus($name, $label, $class)
    {
        // check if it's already there
        $apiResult = civicrm_api3('ParticipantStatusType', 'get', ['name' => $name]);
        if ($apiResult['count'] == 0) {
            $max_weight = (int) CRM_Core_DAO::singleValueQuery("SELECT MAX(weight) FROM civicrm_participant_status_type");
            civicrm_api3(
                'ParticipantStatusType',
                'create',
                [
                    'name' => $name,
                    'label' => $label,
                    'visibility_id' => 'public',
                    'class' => $class,
                    'is_active' => 1,
                    'weight' => $max_weight + 1,
                    'is_reserved' => 1,
                    'is_counted' => 0,
                ]
            );
        }
    }
}
