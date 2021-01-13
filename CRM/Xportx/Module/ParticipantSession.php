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
        // join participant table (strictly speaking not necessary, but we'll do anyway for compatibility)
        $participant_alias = $this->getAlias('participant');
        $base_alias        = $this->getBaseAlias(); // this should be an alias of the civicrm_participant table
        $joins[]           = "LEFT JOIN civicrm_participant {$participant_alias} ON {$participant_alias}.id = {$base_alias}.id";


        $session_base_alias = $this->getAlias('participant_session');
        $base_alias = $this->getBaseAlias(); // this should be an alias of the civicrm_participant table

        $session_indices = $this->getSessionIndices();
        foreach ($session_indices as $session_index) {
            $session_alias = "{$session_base_alias}_{$session_index}";
            $session_id_clause = $this->getSessionIdExpression($session_index);
            $joins[] = "LEFT JOIN civicrm_session {$session_alias} ON {$session_alias}.id {$session_id_clause}";
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

}
