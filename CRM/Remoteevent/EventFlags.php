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
use Civi\RemoteEvent\Event\GetResultEvent;
use Civi\RemoteEvent\Event\GetParamsEvent;

/**
 * Various Flags in events
 */
class CRM_Remoteevent_EventFlags
{
    /** @var string[] list of additional event flags added by RemoteEvent */
    const EVENT_FLAGS = [
        'can_register',
        'can_instant_register',
        'can_edit_registration',
        'can_cancel_registration',
        'is_registered',
        'has_active_waitlist',
    ];

    /** @var string[] list of flags in the event configuration relevant for the UI (full name => internal name) */
    const EVENT_CONFIG_FLAGS = [
        'event_remote_registration.remote_registration_enabled'           => 'remote_registration_enabled',
        'event_remote_registration.remote_use_custom_event_location'      => 'remote_use_custom_event_location',
        'event_remote_registration.remote_disable_civicrm_registration'   => 'remote_disable_civicrm_registration',
        'requires_approval'                                               => 'requires_approval',
    ];

    /**
     * Get all flags for this event
     *
     * @param integer $event_id
     *   the event ID
     *
     * @return array
     *   event data
     */
    public static function getEventFlags($event_id)
    {
        static $event_data = [];
        if (CRM_Utils_Array::value('id', $event_data) != $event_id) {
            // todo: have a common pool for cached events?
            // load event data
            $flag_fields = self::EVENT_CONFIG_FLAGS;
            CRM_Remoteevent_CustomData::resolveCustomFields($flag_fields);
            $event_data = civicrm_api3('Event', 'getsingle', [
                'id'     => $event_id,
                'return' => implode(',', array_keys($flag_fields))
            ]);
            CRM_Remoteevent_CustomData::labelCustomFields($event_data);

            // fill up missing fields (i.e. flag is false)
            foreach (array_keys(self::EVENT_CONFIG_FLAGS) as $field_name) {
                if (!isset($event_data[$field_name])) {
                    $event_data[$field_name] = 0;
                }
            }
        }
        return $event_data;
    }

    /**
     * Check if the alternative location is active in this event
     *
     * @param integer $event_id
     *   the event ID
     *
     * @return boolean
     */
    public static function isRemoteRegistrationEnabled($event_id)
    {
        $event_data = self::getEventFlags($event_id);
        return CRM_Utils_Array::value('event_remote_registration.remote_registration_enabled', $event_data, false);
    }

    /**
     * Check if the alternative location is active in this event
     *
     * @param integer $event_id
     *   the event ID
     *
     * @return boolean
     */
    public static function isAlternativeLocationEnabled($event_id)
    {
        $event_data = self::getEventFlags($event_id);
        return CRM_Utils_Array::value('event_remote_registration.remote_use_custom_event_location', $event_data, false);
    }

    /**
     * Check if the native CiviCRM registration is removed
     *
     * @param integer $event_id
     *   the event ID
     *
     * @return boolean
     */
    public static function isNativeOnlineRegistrationDisabled($event_id)
    {
        $event_data = self::getEventFlags($event_id);
        return CRM_Utils_Array::value('event_remote_registration.remote_disable_civicrm_registration', $event_data, false);
    }

    /**
     * If any of the flags are used as filters, we need to modify the
     *   the query parameters. In particular, we need to drop any limit
     *   parameters, since the flags cannot be evaluated by the underlying
     *   Event.get call
     *
     * @see https://github.com/systopia/de.systopia.remoteevent/issues/4
     *
     * @param GetParamsEvent $get_parameters
     */
    public static function processFlagFilters($get_parameters)
    {
        $flag_filters_applied = [];

        // check if any flag filter is applied
        foreach (self::EVENT_FLAGS as $flag) {
            $flag_value_filter = $get_parameters->getParameter($flag);
            if (isset($flag_value_filter)) {
                $flag_filters_applied[$flag] = $flag_value_filter;
            }
        }

        if (!empty($flag_filters_applied)) {
            // there some flag filters active, we have to remove any limit
            $get_parameters->setLimit(0);
            $get_parameters->setOffset(0);

            // check if we should apply performance enhancements
            $performance_enhancement_enabled = (boolean) Civi::settings()->get('remote_event_get_performance_enhancement');
            $requested_event_ids = $get_parameters->getRequestedEventIDs();

            // apply performance enhancements if there isn't already a restriction on event _IDs_ (<=25)
            if ($performance_enhancement_enabled && ($requested_event_ids != 'fail' || count($requested_event_ids) > 25)) {

                // if it's about registration modalities...
                if (   !empty($get_parameters->getParameter('can_register'))
                    || !empty($get_parameters->getParameter('can_instant_register'))) {
                    // ... we can restrict to those events that are currently potentially open for registration
                    self::restrictToEventsOpenForRegistration($get_parameters);
                }

                // if it's about personalised registration flags...
                $contact_id = $get_parameters->getContactID();
                if ($contact_id) {
                    if (   !empty($get_parameters->getParameter('can_edit_registration'))
                        || !empty($get_parameters->getParameter('can_cancel_registration'))
                        || !empty($get_parameters->getParameter('is_registered'))) {
                        // ... we can restrict to those events to the ones that the contact has registrations for
                        self::restrictToContactsEvents($contact_id, $get_parameters);
                    }
                }

              // TODO: Restrict to events with active waitlist when "has_active_waitlist" is given, for both,
              //       registration and update.
            }
        }
    }

    /**
     * Apply the personalised flags to the result set
     *
     * @param GetResultEvent $result
     */
    public static function calculateFlags(GetResultEvent $result)
    {
        // check whether this is personalised
        $contact_id = $result->getContactID();

        // add flag values
        foreach ($result->getEventData() as &$event) { // todo: optimise queries (over all events)?
            // add flags (might be overwritten by later event handlers)
            $event['participant_registration_count'] = 0; // might be overwritten below
            $event['is_registered'] = 0;  // might be overwritten below
            $cant_register_reason = CRM_Remoteevent_Registration::cannotRegister($event['id'], $contact_id, $event);
            if ($cant_register_reason) {
                $event['can_register'] = 0;
            } else {
                $event['can_register'] = 1;
            }
            // can_instant_register only if can_register
            $event['can_instant_register'] = (int)
                   ($event['can_register'] &&
                   CRM_Remoteevent_Registration::canOneClickRegister($event['id'], $event));

            $event['has_active_waitlist'] = (int) (
                    $event['can_register']
                    && CRM_Remoteevent_RemoteEvent::hasActiveWaitingList($event['id'], $event)
            );

            // add generic can_edit_registration/can_cancel_registration (might be overridden below)
            $cant_edit_reason = CRM_Remoteevent_Registration::cannotEditRegistration($event['id'], $contact_id, $event);
            $event['can_edit_registration'] = (int) empty($cant_edit_reason);
            $event['can_cancel_registration'] = (int) empty($cant_edit_reason);

            if ($contact_id) {
                // PERSONALISED OVERRIDES
                $valid_participant_registrations_count =
                    CRM_Remoteevent_Registration::getRegistrationCount($event['id'], $contact_id);
                $all_participant_registrations_count =
                    CRM_Remoteevent_Registration::getRegistrationCount($event['id'], $contact_id, ['Positive', 'Pending'], [], false);
                $event['participant_registration_count'] = (int) $valid_participant_registrations_count;
                $event['is_registered'] = (int) ($valid_participant_registrations_count > 0);
                $event['can_edit_registration'] = (int)($event['can_edit_registration'] && ($all_participant_registrations_count > 0));
                $event['can_cancel_registration'] = (int)($event['can_cancel_registration'] && ($all_participant_registrations_count > 0));
            }
        }
    }

    /**
     * Apply flag based filters (if any) to the result
     *
     * @see https://github.com/systopia/de.systopia.remoteevent/issues/4
     *
     * @param GetResultEvent $result
     */
    public static function applyFlagFilters(GetResultEvent $result)
    {
        $event_list = &$result->getEventData();
        $query_values = $result->getQueryParameters();

        foreach (self::EVENT_FLAGS as $flag) {
            if (isset($query_values[$flag])) {
                $queried_value = empty($query_values[$flag]) ? 0 : 1;
                foreach (array_keys($event_list) as $event_key) {
                    $event = $event_list[$event_key];
                    $event_value = (int) CRM_Utils_Array::value($flag, $event, -1);
                    if ($event_value != $queried_value) {
                        // filter this event
                        unset($event_list[$event_key]);
                    }
                }
            }
        }

        // Note: the original limit/offset are being applied in the API if they differ from the current values.
    }

    /**
     * Restrict the underlying event query to the ones that
     *   are potentially open for registration
     *
     * @param GetParamsEvent $get_parameters
     */
    public static function restrictToEventsOpenForRegistration($get_parameters)
    {
        // run a simple DB query to find (potentially) open events
        $open_event_ids = [];
        $open_events = CRM_Core_DAO::executeQuery("
            SELECT DISTINCT(event.id) AS event_id
            FROM civicrm_event event
            WHERE event.is_active = 1
              AND COALESCE(event.end_date, event.start_date) >= NOW()
              AND (   event.registration_start_date IS NULL
                   OR event.registration_start_date <= NOW())
              AND (   event.registration_end_date IS NULL
                   OR event.registration_end_date >= NOW())
             ");
        while ($open_events->fetch()) {
            $open_event_ids[] = $open_events->event_id;
        }

        // restrict the event query to those events
        $get_parameters->restrictToEventIds($open_event_ids);
    }

    /**
     * Restrict the underlying event query to the ones that
     *   are potentially open for registration
     *
     * @param integer $contact_id
     * @param GetParamsEvent $get_parameters
     */
    public static function restrictToContactsEvents($contact_id, $get_parameters)
    {
        // run a simple DB query to find all events linked to the contact
        $contact_id = (int) $contact_id;
        if ($contact_id) {
            $contact_event_ids = [];
            $contact_events = CRM_Core_DAO::executeQuery("
                SELECT DISTINCT(event_id) AS event_id
                FROM civicrm_participant
                WHERE contact_id = {$contact_id}");
            while ($contact_events->fetch()) {
                $contact_event_ids[] = $contact_events->event_id;
            }

            // restrict the event query to those events
            $get_parameters->restrictToEventIds($contact_event_ids);
        }
    }
}
