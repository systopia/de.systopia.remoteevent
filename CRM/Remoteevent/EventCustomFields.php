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

use CRM_Remoteevent_ExtensionUtil as E;
use Civi\RemoteEvent\Event\GetResultEvent;
use Civi\RemoteEvent\Event\GetParamsEvent;

/**
 * Deals with issues/problems with custom fields on the Event (and therefore RemoteEvent) entity.
 */
class CRM_Remoteevent_EventCustomFields
{
    /**
     * If there are filters in the RemoteEvent.get call that refer to
     *   custom fields, in particular multi-value ones, then this
     *   function will try to mitigate some of the quirks of the CiviCRM API
     *
     * @see https://github.com/systopia/de.systopia.remoteevent/issues/5
     *
     * @param GetParamsEvent $get_parameters
     */
    public static function processCustomFieldFilters($get_parameters)
    {
        // extract the filters that affect multi-value custom fields
        $multi_value_custom_field_filters = [];
        $current_query = $get_parameters->getParameters();
        CRM_Remotetools_CustomData::resolveCustomFields($current_query);
        foreach ($current_query as $field_name => $value) {
            if (substr($field_name, 0, 7) == 'custom_' && !empty($value)) {
                // this is a custom field, check if it's multi-value
                $custom_field_id = substr($field_name, 7);
                $custom_field = CRM_Remotetools_CustomData::getFieldSpecs($custom_field_id);
                if (!empty($custom_field['serialize'])) {
                    // this is a multi-value custom field
                    //  first: add it to the list
                    $multi_value_custom_field_filters[$custom_field_id] = $value;

                    // then: remove the original query parameter
                    $custom_group = CRM_Remotetools_CustomData::getGroupSpecs($custom_field['custom_group_id']);
                    $get_parameters->removeParameter("{$custom_group['name']}.{$custom_field['name']}");
                }
            }
        }

        // if there are filters that affect multi-value custom fields,
        //  we need to restrict the event IDs as a workaround
        foreach ($multi_value_custom_field_filters as $custom_field_id => $value) {
            self::filterEventIdsByCustomFieldValue($get_parameters, $custom_field_id, $value);
        }
    }

    /**
     * Restrict the underlying event query to the ones that
     *   are potentially open for registration
     *
     * @param integer $contact_id
     * @param GetParamsEvent $get_parameters
     */
    public static function filterEventIdsByCustomFieldValue($get_parameters, $custom_field_id, $values)
    {
        $custom_field = CRM_Remotetools_CustomData::getFieldSpecs($custom_field_id);
        $custom_group = CRM_Remotetools_CustomData::getGroupSpecs($custom_field['custom_group_id']);
        if ($custom_field && $custom_group) {
            // remark: assuming
            //  value=[IN => [values]] means "contains any of these"
            //  value=[values]         means "contains all of these"

            // extract a list of AND groups
            $OR_AND_GROUPS = [];
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    if (strtolower($key) == 'in') {
                        if (!is_array($value)) {
                            $value = explode(',', $value);
                        }
                        $OR_AND_GROUPS[] = $value;
                    } else {
                        $OR_AND_GROUPS[] = [$value];
                    }
                }
            } else {
                $values = explode(',', $values);
                $OR_AND_GROUPS = [$values];
            }


            // build the where clause
            $and_clauses = [];
            foreach ($OR_AND_GROUPS as $OR_GROUP) {
                $or_clauses = [];
                foreach ($OR_GROUP as $OR_CLAUSE) {
                    $value = CRM_Utils_Array::implodePadded($OR_CLAUSE);
                    $or_clauses[] = "{$custom_field['column_name']} LIKE '%{$value}%'";
                }
                $and_clauses[] = implode(' OR ', $or_clauses);
            }
            $where_clause = implode(' AND ', $and_clauses);
            if (empty($where_clause)) {
                $where_clause = 'TRUE';
            }

            // run a simple DB query to find all events linked to the contact
            $matching_values_event_ids = [];
            $custom_field_events = CRM_Core_DAO::executeQuery("
                SELECT DISTINCT(entity_id) AS event_id
                FROM {$custom_group['table_name']}
                WHERE {$where_clause}");
            while ($custom_field_events->fetch()) {
                $matching_values_event_ids[] = $custom_field_events->event_id;
            }

            // restrict the event query to those events
            $get_parameters->restrictToEventIds($matching_values_event_ids);

        } else {
            Civi::log()->debug("Error processing custom field [{$custom_field_id}], filter fix not applied.");
        }
    }
}
