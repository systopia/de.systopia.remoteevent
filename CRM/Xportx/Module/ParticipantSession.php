<?php
/*-------------------------------------------------------+
| SYSTOPIA EXTENSIBLE EXPORT EXTENSION                   |
| Copyright (C) 2018 SYSTOPIA                            |
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

use CRM_Xportx_ExtensionUtil as E;

/**
 * Provides ParticipantSession data, i.e. the first X number of
 *      sessions the participant is registered to
 *
 * You can access the session data with the key
 *   session[index]_[session_field_name], e.g. session3_start_date
 *
 * You can define the number of sessions
 *
 * This exporter is only available for participant exports
 */
class CRM_Xportx_Module_ParticipantSession extends CRM_Xportx_Module
{
    /**
     * This module can do with any base_table
     * (as long as it has a contact_id column)
     */
    public function forEntity()
    {
        return 'Participant';
    }

    /**
     * Get this module's preferred alias.
     * Must be all lowercase chars: [a-z]+
     */
    public function getPreferredAlias()
    {
        return 'participant_session';
    }

    /**
     * get the list of session indices that have been joined,
     *   default is 5
     *
     * @return array
     */
    public function getSessionIndices()
    {
        $count = CRM_Utils_Array::value('count', $this->config, 5);
        return range(1, $count);
    }

    /**
     * Get an sql expression to refer to the session with the given index
     *
     * @param integer $session_index
     *
     * @todo allow custom order bys
     *
     * @return string SQL expression
     */
    protected function getSessionIdExpression($session_index)
    {
        $offset = $session_index - 1;
        $participant_id = $this->getAlias('participant') . '.id';
        $subquery_alias = $this->getAlias('participant_session') . "_{$session_index}_subquery";
        return " IN (SELECT * FROM (
            SELECT session.id
            FROM civicrm_participant_session participant_session
            LEFT JOIN civicrm_session session
                   ON session.id = participant_session.session_id
            WHERE participant_session.participant_id = {$participant_id} 
            ORDER BY session.start_date ASC
            LIMIT 1
            OFFSET {$offset}
        ) {$subquery_alias} ) ";
    }

    /**
     * add this module's joins clauses to the list
     * they can only refer to the main contact table
     * "contact" or other joins from within the module
     */
    public function addJoins(&$joins)
    {
        $session_base_alias = $this->getAlias('participant_session');
        $base_alias = $this->getBaseAlias(); // this should be an alias of the civicrm_participant table

        $session_indices = $this->getSessionIndices();
        foreach ($session_indices as $session_index) {
            $temp_table_name = $this->getAlias('participant_session_tmp') . "_{$session_index}";
            $joins[] = "LEFT JOIN {$temp_table_name} ON {$temp_table_name}.participant_id = {$base_alias}.id";
            $session_alias = "{$session_base_alias}_{$session_index}";
            $joins[] = "LEFT JOIN civicrm_session {$session_alias} ON {$temp_table_name}.session_id = {$session_alias}.id";
        }
    }



    /**
     * add this module's select clauses to the list
     * they can only refer to the main contact table
     * "contact" or this module's joins
     */
    public function addSelects(&$selects)
    {
        $session_base_alias = $this->getAlias('participant_session');
        $value_prefix       = $this->getValuePrefix();
        foreach ($this->config['fields'] as $field_spec) {
            $field_name = $field_spec['key'];
            if (preg_match("/^session_([0-9]+)_(\w+)$/", $field_name, $match)) {
                $session_index = $match[1];
                $session_alias = "{$session_base_alias}_{$session_index}";
                $session_field_name = $match[2];
                $selects[] = "{$session_alias}.{$session_field_name} AS {$value_prefix}{$field_name}";
            }
        }
    }

    /**
     * Create N temp-table containing the N-th session
     *  (according to the order)
     *
     *
     * @todo this is *really* ugly, is there a non-iterative way to do this?
     *
     * @param array $entity_ids
     *  IDs
     */
    public function createTempTables($entity_ids)
    {
        // create N temp tables
        $order = "session.start_date ASC"; // todo: config?
        $session_indices = $this->getSessionIndices();
        $max_index = max($session_indices);
        $entity_id_list = implode(',', $entity_ids);
        $tmp_table_names = [];
        foreach ($session_indices as $session_index) {
            $temp_table_name = $this->getAlias('participant_session_tmp') . "_{$session_index}";
            CRM_Core_DAO::executeQuery(
                "CREATE TEMPORARY TABLE {$temp_table_name} (
             `session_id`       int unsigned,
             `participant_id`   int unsigned,
             INDEX participant_id(participant_id)
            ) ENGINE=MEMORY;");
            $tmp_table_names[$session_index] = $temp_table_name;
        }

        // run the query to fill them
        $data_query = CRM_Core_DAO::executeQuery("
            SELECT 
                   participant_id AS participant_id,
                   session_id     AS session_id
            FROM civicrm_participant_session participant_session
            LEFT JOIN civicrm_session session
                   ON session.id = participant_session.session_id
            WHERE participant_session.participant_id IN ({$entity_id_list})
            ORDER BY participant_session.participant_id ASC, {$order}");
        $current_participant = 0;
        $current_participant_counter = 0;
        while ($data_query->fetch()) {
            // reset with next participant
            if ($data_query->participant_id != $current_participant) {
                $current_participant = $data_query->participant_id;
                $current_participant_counter = 0;
            }

            // process current entry
            $current_participant_counter++;
            if ($current_participant_counter <= $max_index) {
                CRM_Core_DAO::executeQuery(
                    "INSERT INTO {$tmp_table_names[$current_participant_counter]} 
                    (participant_id,session_id) VALUES ({$data_query->participant_id}, {$data_query->session_id})"
                );
            }
        }
    }
}
