<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Some tools to be used by various parts of the system
 */
class CRM_Remoteevent_Tools {

  /**
   * Get a localised list of option group values for the field keys
   *
   * @param string|integer $option_group_id
   *   identifier for the option group
   *
   * @return array list of key => (localised) label
   */
  public static function getOptions($option_group_id, $locale, $params = [], $use_name = FALSE, $sort = 'weight asc') {
    $option_list = [];
    $query       = [
      'option.limit'    => 0,
      'option_group_id' => $option_group_id,
      'return'          => 'value,label,name',
      'is_active'       => 1,
      'sort'            => $sort,
    ];

    // extend/override query
    foreach ($params as $key => $value) {
      $query[$key] = $value;
    }

    // check cache
    static $result_cache = [];
    $cache_key = serialize($query);
    if (isset($result_cache[$cache_key])) {
      $result = $result_cache[$cache_key];
    }
    else {
      // run query
      $result = civicrm_api3('OptionValue', 'get', $query);
      $result_cache[$cache_key] = $result;
    }

    // compile result
    $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
    foreach ($result['values'] as $entry) {
      if ($use_name) {
        $option_list[$entry['name']] = $l10n->ts($entry['label']);
      }
      else {
        $option_list[$entry['value']] = $l10n->ts($entry['label']);
      }
    }

    return $option_list;
  }

  /**
   * There seems to be an issue with cloning custom data from event templates (through the UI),
   *   so this function makes sure, that all custom tables have been copied
   *
   * @param int $original_event_id
   *   the original event ID
   *
   * @param int $new_event_id
   *   the new cloned/copied event ID
   *
   * @param array $exclude_tables
   *   table names to exclude
   *
   * @see https://github.com/systopia/de.systopia.remoteevent/issues/28
   */
  public static function cloneEventCustomDataTables($original_event_id, $new_event_id, $exclude_tables = []) {
    $all_tables = CRM_Core_DAO::executeQuery("SELECT table_name FROM civicrm_custom_group WHERE extends = 'Event';");
    while ($all_tables->fetch()) {
      $table_name = $all_tables->table_name;
      if (!in_array($table_name, $exclude_tables)) {
        self::cloneEventCustomDataTable($table_name, $original_event_id, $new_event_id);
      }
    }
  }

  /**
   * There seems to be an issue with cloning custom data from event templates (through the UI),
   *  so this function copies everything from the given event custom table to the new event
   *
   * @param string $custom_event_table_name
   *   the table name of a custom data table
   *
   * @param int $original_event_id
   *   the original event ID
   *
   * @param int $new_event_id
   *   the new cloned/copied event ID
   *
   * @see https://github.com/systopia/de.systopia.remoteevent/issues/28
   */
  public static function cloneEventCustomDataTable($custom_event_table_name, $original_event_id, $new_event_id) {
    $remote_registration_entry_exists = CRM_Core_DAO::singleValueQuery(
        "SELECT id FROM {$custom_event_table_name} WHERE entity_id = %1",
        [1 => [$new_event_id, 'Integer']]);
    if (!$remote_registration_entry_exists) {
      // this *should* have been copied with the clone/copy routine, but it wasn't. So, we clone the line via SQL:
      Civi::log()->debug("Looks like table {$custom_event_table_name} has not been copied from event template, copying data...");
      CRM_Core_DAO::executeQuery("CREATE TEMPORARY TABLE _tmp_clone_remote_registration AS SELECT * FROM {$custom_event_table_name} WHERE entity_id = {$original_event_id};");
      CRM_Core_DAO::executeQuery("UPDATE _tmp_clone_remote_registration SET entity_id = {$new_event_id};");
      CRM_Core_DAO::executeQuery("UPDATE _tmp_clone_remote_registration SET id = (1 + (SELECT MAX(id) FROM {$custom_event_table_name}));");
      CRM_Core_DAO::executeQuery("INSERT INTO {$custom_event_table_name} SELECT * FROM _tmp_clone_remote_registration;");
      CRM_Core_DAO::executeQuery('DROP TABLE _tmp_clone_remote_registration;');
      Civi::log()->debug("Data of table {$custom_event_table_name} copied.");
    }
  }

}
