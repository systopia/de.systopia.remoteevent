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
class CRM_Remoteevent_Upgrader extends CRM_Extension_Upgrader_Base
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
        $customData->syncOptionGroup(E::path('resources/option_group_session_slot.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_session_type.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_session_category.json'));

        // add participant status
        $this->addParticipantStatus('Invited', E::ts('Invited'), 'Waiting');

        // add constraints
        $this->makeExternalIdentifierUnique();
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
        $this->executeSqlFile('sql/session.sql');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/option_group_session_slot.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_session_type.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_session_category.json'));
        return true;
    }

    /**
     * Adding more fields and a 'OneClick' profile
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0008()
    {
        $this->ctx->log->info('Adding Update Profiles');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        return true;
    }

    /**
     * Adding external_identifier field
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0010()
    {
        $this->ctx->log->info('Adding event external_identifier');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        $this->makeExternalIdentifierUnique();
        return true;
    }

    /**
     * Adding 'registration suspended' field
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0011()
    {
        $this->ctx->log->info('Updating data structures');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        return true;
    }

    /**
     * Adding configurable XCM profiles
     *
     * @see https://github.com/systopia/de.systopia.remoteevent/issues/25
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0013()
    {
        $this->ctx->log->info('Updating data structures');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));

        // clean template cache
        $config = CRM_Core_Config::singleton();
        $config->cleanup(1, false);

        return true;
    }

    /**
     * Adjusting Registration Profile fields
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0015()
    {
        $this->ctx->log->info('Updating RegistrationProfile data structures');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        // Attribute "text_length" is dropped by CRM_Remoteevent_CustomData.
        // It only updates fields that are set in the current field API get
        // result. NULL values are dropped by APIv3.
        \Civi\Api4\CustomField::update(FALSE)
          ->addValue('text_length', 1024)
          ->addWhere('custom_group_id:name', '=', 'event_remote_registration')
          ->addWhere('name', 'IN', ['remote_registration_profiles', 'remote_registration_update_profiles'])
          ->execute();
        $this->migrate_profile_option_values_to_option_names();
        return true;
    }

    /**
     * Synchronizes custom fields for CiviRemote Event registration
     * configuration fields, as two fields need to be marked as not searchable
     * for avoiding exceeding index lengths with bigger VARCHAR lengths
     * introduced with upgrade_0015().
     *
     * @return bool
     * @throws \Exception
     */
    public function upgrade_0016(): bool
    {
        $this->ctx->log->info('Updating RegistrationProfile data structures');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        return true;
    }

    public function upgrade_0017(): bool
    {
        $this->ctx->log->info('Updating database schema');
        $this->executeSql("ALTER TABLE civicrm_session
        MODIFY `event_id` int unsigned NOT NULL COMMENT 'FK to Event'");
        $this->executeSql("ALTER TABLE civicrm_participant_session
        MODIFY `session_id` int unsigned NOT NULL COMMENT 'FK to Session'");
        $this->executeSql("ALTER TABLE civicrm_participant_session
        MODIFY `participant_id` int unsigned NOT NULL COMMENT 'FK to Participant'");
        return true;
    }

    /**
     * Synchronizes custom fields for CiviRemote Event registration
     * configuration fields, adding a new field for selecting a registration
     * profile to be used for additional participants.
     *
     * @return bool
     * @throws \Exception
     */
    public function upgrade_0018(): bool
    {
        $this->ctx->log->info('Updating RegistrationProfile data structures');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        return true;
    }

  /**
   * Synchronizes custom fields for CiviRemote Event registration configuration
   * adding a new field to require a user account for registration.
   */
  public function upgrade_0019(): bool
  {
      $this->ctx->log->info('Updating RegistrationProfile data structures');
      $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
      $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
    
      return true;
  }

  /**
     * Synchronizes custom groups to make them reserved. This is necessary since
     * CiviCRM 5.71.0 because of this change:
     * https://github.com/civicrm/civicrm-core/commit/1f511cc1a07e0c5e9902b0c053f2c4e4bbf45784
     *
     * @throws \Exception
     */
    public function upgrade_0020(): bool
    {
        $this->ctx->log->info('Updating data structures');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        $customData->syncCustomGroup(E::path('resources/custom_group_alternative_location.json'));

        return true;
    }

    /****************************************************************
     **                       HELPER FUNCTIONS                     **
     ****************************************************************/

    /**
     * Migrate previous profile option values to profile option names.
     */
    protected function migrate_profile_option_values_to_option_names(): void
    {
        // Mapping of option value to option name of profiles.
        $profile_names = \Civi\Api4\OptionValue::get(false)
            ->addSelect('value', 'name')
            ->addWhere('option_group_id:name', '=', 'remote_registration_profiles')
            ->execute()
            ->indexBy('value')
            ->column('name');

        // Replace current values
        $remote_registration_query = CRM_Core_DAO::executeQuery("
            SELECT
                id, default_profile, profiles, default_update_profile, update_profiles
            FROM civicrm_value_remote_registration
        ");
        while ($remote_registration_query->fetch()) {
            $default_profile = $profile_names[$remote_registration_query->default_profile] ?? NULL;
            $default_update_profile = $profile_names[$remote_registration_query->default_update_profile] ?? NULL;
            $profiles = $this->convertOptionValuesToNames(
                    $remote_registration_query->profiles,
                    $profile_names
            );
            $update_profiles = $this->convertOptionValuesToNames(
                    $remote_registration_query->update_profiles,
                    $profile_names
            );

            CRM_Core_DAO::executeQuery("
                UPDATE civicrm_value_remote_registration
                SET
                    default_profile = '{$default_profile}',
                    default_update_profile = '{$default_update_profile}',
                    profiles = '{$profiles}',
                    update_profiles = '{$update_profiles}'
                WHERE id = '{$remote_registration_query->id}';
            ");
        }
    }

    /**
     * @param string|null $values Padded option values.
     * @param array $mapping Option value to option name mapping.
     *
     * @return string Padded option names.
     */
    protected function convertOptionValuesToNames(?string $values, array $mapping): string
    {
        $names = [];
        foreach (\CRM_Utils_Array::explodePadded($values) ?? [] as $value) {
            if (isset($mapping[$value])) {
                $names[] = $mapping[$value];
            }
        }
        return \CRM_Utils_Array::implodePadded($names);
    }

    /**
     * Add a unique index on the event external identifiers
     */
    protected function makeExternalIdentifierUnique()
    {
        // gather field / group info
        $external_identifier_field = CRM_Remoteevent_CustomData::getCustomField(
            'event_remote_registration',
            'remote_registration_external_identifier'
        );
        if (empty($external_identifier_field)) {
            throw new Exception(
                "Field 'event_remote_registration.remote_registration_external_identifier' does not exist!"
            );
        }
        $external_identifier_group = CRM_Remoteevent_CustomData::getGroupSpecs(
            $external_identifier_field['custom_group_id']
        );

        // add unique key (if not already there)
        $find_index_query = "SHOW INDEX FROM `{$external_identifier_group['table_name']}`
                             WHERE column_name = '{$external_identifier_field['column_name']}'";
        $index = CRM_Core_DAO::executeQuery($find_index_query);
        if (!$index->fetch()) {
            // index missing: add unique key
            CRM_Core_DAO::executeQuery(
                "
                ALTER TABLE `{$external_identifier_group['table_name']}`
                ADD UNIQUE KEY `UI_external_identifier` (`{$external_identifier_field['column_name']}`)"
            );
        }
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
            $max_weight = (int)CRM_Core_DAO::singleValueQuery(
                "SELECT MAX(weight) FROM civicrm_participant_status_type"
            );
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
