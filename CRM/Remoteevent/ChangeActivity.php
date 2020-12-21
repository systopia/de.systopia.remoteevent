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
 * Tools to create a participant change activity
 */
class CRM_Remoteevent_ChangeActivity
{

    const RECORD_PARTICIPANT_ID = 0;
    const RECORD_PARTICIPANT_DATA = 1;
    const RECORD_PARTICIPANT_UPDATE_HAS_NO_CUSTOM_FIELDS = 2;

    /** @var array stack of [participant_id, data] tuples */
    protected static $record_stack = [];

    /**
     * Get the activity type ID to be used to record actviities
     *
     * @return integer|null
     *   activity type ID or null if disabled
     */
    public static function getActivityTypeID()
    {
        return Civi::settings()->get('remote_participant_change_activity_type_id');
    }


    /**
     * Record a participant status before a change
     *
     * @param integer $participant_id
     * @param array $participant_data
     */
    public static function recordPre($participant_id, $participant_data)
    {
        if (self::getActivityTypeID()) { // is this enabled?
            // check if there is custom fields involved
            $has_no_custom_fields = true;
            foreach ($participant_data as $key => $value) {
                if (substr($key, 0, 7) == 'custom_') {
                    $has_no_custom_fields = false;
                    break;
                }
            }

            if (empty($participant_id)) {
                // this is a new contact
                array_push(self::$record_stack, [0, [], $has_no_custom_fields]);

            } else {

                // load contact
                $current_values = self::getParticipantData($participant_id);
                array_push(self::$record_stack, [$participant_id, $current_values, $has_no_custom_fields]);
            }

        }
    }

    /**
     * Record a participant status after a change, and trigger any matching rules
     *
     * @param integer $participant_id
     */
    public static function recordPost($participant_id, $custom_fields_done = false)
    {
        if (self::getActivityTypeID()) { // is this enabled?
            // if this change contains custom fields, we have to wait for that, too.
            $record = end(self::$record_stack);
            if ($record[self::RECORD_PARTICIPANT_UPDATE_HAS_NO_CUSTOM_FIELDS] || $custom_fields_done) {
                $record = array_pop(self::$record_stack);
                $pre_participant_id = $record[self::RECORD_PARTICIPANT_ID];
                if ($pre_participant_id) {
                    if ($pre_participant_id != $participant_id) {
                        Civi::log()->warning("RemoteEvent: Participant monitoring issue, stack inconsistent.");
                    } else {
                        $previous_values = $record[self::RECORD_PARTICIPANT_DATA];
                        $current_values = self::getParticipantData($participant_id);
                        self::createDiffActivity($previous_values, $current_values);
                    }
                } else {
                    // this is a new participant -> skip
                }
            }
        }
    }

    /**
     * Test if the currently processed record contains custom fields
     * In this case, we want to wait for the custom field update hook rather then the regular post hook
     */
    protected static function currentRecordHasNoCustomFields()
    {
        $record = end(self::$record_stack);
        $update = $record[1];
        return false;
    }

    /**
     * Get the current participant data
     *
     * @param integer $participant_id
     *   the participant ID
     */
    protected static function getParticipantData($participant_id)
    {
        // todo be more efficient? tap into caches? use SQL?
        // also: limit return parameters?
        $participant_raw = civicrm_api3('Participant', 'getsingle', ['id' => $participant_id]);
        $participant_filtered = [];
        foreach ($participant_raw as $field_name => $value) {
            if (substr($field_name, 0, 12) == 'participant_') {
                $participant_filtered[$field_name] = $value;

            } elseif (preg_match('/^custom_[0-9]+$/', $field_name)) {
                $participant_filtered[$field_name] = $value;

            } elseif ($field_name == 'contact_id' || $field_name == 'event_id') {
                $participant_filtered[$field_name] = $value;

            } else {
                // drop value
            }
        }

        // use the status/role name if available
        if (!empty($participant_filtered['participant_status'])) {
            $participant_filtered['participant_status_id'] = $participant_filtered['participant_status'];
            unset($participant_filtered['participant_status']);
        }
        if (!empty($participant_filtered['participant_role'])) {
            $participant_filtered['participant_role_id'] = $participant_filtered['participant_role'];
            unset($participant_filtered['participant_role']);
        }
        return $participant_filtered;
    }

    /**
     * Create a new activity of there are differences
     *
     * @param array $previous_values
     * @param array $current_values
     */
    protected static function createDiffActivity($previous_values, $current_values)
    {
        // see if there's a diff
        $differing_attributes = [];
        $all_attributes = array_keys($previous_values) + array_keys($previous_values);
        foreach ($all_attributes as $attribute) {
            if (in_array($attribute, ['status', 'role'])) {
                // skip those
                continue;
            }

            $previous_value = CRM_Utils_Array::value($attribute, $previous_values);
            $current_value  = CRM_Utils_Array::value($attribute, $current_values);
            if ($previous_value != $current_value) {
                $differing_attributes[] = $attribute;
            }
        }

        // create a activity if necessary
        if (!empty($differing_attributes)) {
            /** @var array $field_data will store key => [label, old_value, new_value]*/
            $field_data = [];

            // gather some data
            $participant_code_fields = CRM_Event_DAO_Participant::fields();
            $custom_fields = [];

            foreach ($differing_attributes as $attribute) {
                // first: create record
                $field_data[$attribute] = [
                    'old_value' => CRM_Utils_Array::value($attribute, $previous_values),
                    'new_value' => CRM_Utils_Array::value($attribute, $current_values),
                ];

                // look for labels
                if (substr($attribute, 0, 7) == 'custom_') {
                    // this is a custom field -> will do a lookup below
                    $custom_fields[$attribute] = substr($attribute, 7);
                    $field_data[$attribute]['custom_field_id'] = $custom_fields[$attribute];

                } else if (isset($participant_code_fields[$attribute])) {
                    // this is a core field
                    $field_data[$attribute]['label'] = $participant_code_fields[$attribute]['title'];

                } else {
                    // this is weird, it should be either
                    $field_data[$attribute]['label'] = $attribute;
                }
            }

            // load+label custom fields
            CRM_Remoteevent_CustomData::cacheCustomFields($custom_fields);
            foreach ($custom_fields as $attribute => $custom_field_id) {
                $custom_field = CRM_Remoteevent_CustomData::getFieldSpecs($custom_field_id);
                $field_data[$attribute]['label'] = $custom_field['label'];

                // map the option values
                if (!empty($custom_field['option_group_id'])) {
                    foreach (['old_value', 'new_value'] as $value_key) {
                        if (!empty($field_data[$attribute][$value_key])) {
                            try {
                                $field_data[$attribute][$value_key] = civicrm_api3('OptionValue', 'getvalue', [
                                    'option_group_id' => $custom_field['option_group_id'],
                                    'value'           => $field_data[$attribute][$value_key],
                                    'return'          => 'label'
                                ]);
                            } catch (CiviCRM_API3_Exception $ex) {
                                $field_data[$attribute][$value_key] = E::ts("%1 (invalid)", [1 => $field_data[$attribute][$value_key]]);
                            }
                        }
                    }
                }
            }

            // todo: format values

            // render and create activity
            try {
                // determine source contact ID
                $source_contact_id = CRM_Core_Session::getLoggedInContactID();
                if (empty($source_contact_id)) {
                    $source_contact_id = $current_values['contact_id'];
                }

                // render the details
                static $template = null;
                if ($template === null) {
                    $template = 'string:' . file_get_contents(E::path('resources/participant_change_activity.tpl'));
                }
                $smarty = CRM_Core_Smarty::singleton();
                $smarty->assign('previous_values', $previous_values);
                $smarty->assign('current_values', $current_values);
                $smarty->assign('diff_data', $field_data);
                $details = $smarty->fetch($template);

                $activity_data = [
                    'activity_type_id'  => self::getActivityTypeID(),
                    'status_id'         => 'Completed',
                    'source_contact_id' => $source_contact_id,
                    'target_contact_id' => $current_values['contact_id'],
                    'subject'           => E::ts("Participant Updated"),
                    'details'           => $details,
                ];
                civicrm_api3('Activity', 'create', $activity_data);
            } catch (CiviCRM_API3_Exception $ex) {
                Civi::log()->debug("Couldn't create activity: " . json_encode($activity_data) . ' - error was: ' . $ex->getMessage());
            }
        }
    }
}
