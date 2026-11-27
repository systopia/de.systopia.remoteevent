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

/**
 * Collection of upgrade steps.
 */
class CRM_Remoteevent_Upgrader extends CRM_Extension_Upgrader_Base {

  public function postInstall(): void {
    $this->makeExternalIdentifierUnique();
  }

  /**
   * Adding more fields and a 'OneClick' profile
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0004(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Adding participant status invited
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0005(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Adding GTAC field
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0006(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Added Sessions (Workshops)
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0007(): bool {
    $this->ctx->log->info('Installing Sessions');
    $this->executeSqlFile('sql/session.sql');
    return TRUE;
  }

  /**
   * Adding more fields and a 'OneClick' profile
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0008(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Adding external_identifier field
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0010(): bool {
    $this->ctx->log->info('Adding event external_identifier');
    $this->makeExternalIdentifierUnique();
    return TRUE;
  }

  /**
   * Adding 'registration suspended' field
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0011(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Adding configurable XCM profiles
   *
   * @see https://github.com/systopia/de.systopia.remoteevent/issues/25
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0013(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Adjusting Registration Profile fields
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0015(): bool {
    $this->ctx->log->info('Updating RegistrationProfile data structures');
    $this->migrate_profile_option_values_to_option_names();
    return TRUE;
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
  public function upgrade_0016(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  public function upgrade_0017(): bool {
    $this->ctx->log->info('Updating database schema');
    $this->executeSql("ALTER TABLE civicrm_session
        MODIFY `event_id` int unsigned NOT NULL COMMENT 'FK to Event'");
    $this->executeSql("ALTER TABLE civicrm_participant_session
        MODIFY `session_id` int unsigned NOT NULL COMMENT 'FK to Session'");
    $this->executeSql("ALTER TABLE civicrm_participant_session
        MODIFY `participant_id` int unsigned NOT NULL COMMENT 'FK to Participant'");
    return TRUE;
  }

  /**
   * Synchronizes custom fields for CiviRemote Event registration
   * configuration fields, adding a new field for selecting a registration
   * profile to be used for additional participants.
   *
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0018(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Synchronizes custom fields for CiviRemote Event registration configuration
   * adding a new field to require a user account for registration.
   */
  public function upgrade_0019(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Synchronizes custom groups to make them reserved. This is necessary since
   * CiviCRM 5.71.0 because of this change:
   * https://github.com/civicrm/civicrm-core/commit/1f511cc1a07e0c5e9902b0c053f2c4e4bbf45784
   *
   * @throws \Exception
   */
  public function upgrade_0020(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Synchronizes custom groups to add new fields.
   *
   * @throws \Exception
   */
  public function upgrade_0021(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Clears is_view on registration profile fields so they are carried over
   * when an event is copied.
   *
   * @throws \Exception
   */
  public function upgrade_0022(): bool {
    // Replaced by managed entities.
    return TRUE;
  }

  /**
   * Migrate previous profile option values to profile option names.
   */
  private function migrate_profile_option_values_to_option_names(): void {
    // Mapping of option value to option name of profiles.
    $profile_names = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value', 'name')
      ->addWhere('option_group_id:name', '=', 'remote_registration_profiles')
      ->execute()
      ->indexBy('value')
      ->column('name');

    // Replace current values
    $remote_registration_query = CRM_Core_DAO::executeQuery('
            SELECT
                id, default_profile, profiles, default_update_profile, update_profiles
            FROM civicrm_value_remote_registration
        ');
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
  private function convertOptionValuesToNames(?string $values, array $mapping): string {
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
  private function makeExternalIdentifierUnique(): void {
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

}
