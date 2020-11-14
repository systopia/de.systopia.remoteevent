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
use \Civi\RemoteParticipant\Event\RegistrationEvent as RegistrationEvent;
use \Civi\RemoteParticipant\Event\GetCreateParticipantFormEvent as GetCreateParticipantFormEvent;

/**
 * Class to coordinate event registrations (RemoteParticipant)
 */
class CRM_Remoteevent_Registration
{
    const STAGE1_CONTACT_IDENTIFICATION = 5000;
    const STAGE2_PARTICIPANT_CREATION   = 0;
    const STAGE3_POSTPROCESSING         = -5000;
    const STAGE4_COMMUNICATION          = -10000;

    const BEFORE_CONTACT_IDENTIFICATION = self::STAGE1_CONTACT_IDENTIFICATION + 50;
    const AFTER_CONTACT_IDENTIFICATION  = self::STAGE1_CONTACT_IDENTIFICATION - 50;
    const BEFORE_PARTICIPANT_CREATION   = self::STAGE2_PARTICIPANT_CREATION + 50;
    const AFTER_PARTICIPANT_CREATION    = self::STAGE2_PARTICIPANT_CREATION - 50;

    /** @var array list of [contact_id -> list of participant data] */
    protected static $cached_registration_data = [];

    /** @var array list of event_ids */
    protected static $cached_registration_event_ids = [];

    /**
     * Allow the system to cache the registration data for the given contact
     *   restricted to the event IDs submitted
     *
     * @param array $event_ids
     *    list of event IDs
     *
     * @param integer $contact_id
     *    contact id
     */
    public static function cacheRegistrationData($event_ids, $contact_id)
    {
        if ($contact_id) {
            // check if all event IDs are queried
            if (array_diff($event_ids, self::$cached_registration_event_ids)) {
                // some of the event_ids aren't cached -> reset
                self::$cached_registration_event_ids = [];
            }

            // check if we need to initialise
            if (count(self::$cached_registration_event_ids) == 0) {
                // initialise cache
                self::$cached_registration_event_ids = $event_ids;
                self::$cached_registration_data = [];
            }

            $participant_params = [
                'contact_id'   => $contact_id,
                'option.limit' => 0,
                'sequential'   => 1,
            ];
            if (!empty($event_ids)) {
                $participant_params['event_id'] = ['IN' => $event_ids];
            }
            $participant_query = civicrm_api3('Participant', 'get', $participant_params);
            self::$cached_registration_data[$contact_id] = $participant_query['values'];
        }
    }

    /**
     * Get a list of participant objects
     *
     * @param integer $event_id
     *   the event
     *
     * @param integer $contact_id
     *   the contact
     *
     * @return array
     *    list of participants
     */
    public static function getRegistrations($event_id, $contact_id) {
        // skip bogus calls
        if (empty($contact_id)) {
            return [];
        }

        // make sure this event/contact was cached
        if (!in_array($event_id, self::$cached_registration_event_ids)) {
            // this shouldn't happen
            self::cacheRegistrationData([$event_id], $contact_id);
        }
        if (!isset(self::$cached_registration_data[$contact_id])) {
            self::cacheRegistrationData([$event_id], $contact_id);
        }

        return self::$cached_registration_data[$contact_id];
    }

    /**
     * Get a list of participant objects
     *
     * @param integer $event_id
     *   the event
     *
     * @param integer $contact_id
     *   the contact
     */
    public static function invalidateRegistrationCache($contact_id, $event_id) {
        // unset the registration data
        unset(self::$cached_registration_data[$contact_id]);

        // update the indicator
        $event_cached_key = array_search($event_id, self::$cached_registration_event_ids);
        if ($event_cached_key !== false) {
            unset(self::$cached_registration_event_ids[$event_cached_key]);
        }
    }

    /**
     * Check if the given contact can register for the given event
     *
     * @param integer $event_id
     *      the event you want to register to
     *
     * @param integer|null $contact_id
     *      the contact trying to register (in case of restricted registration)
     *
     * @param array $event_data
     *      the data known of the event (so we don't have to pull it ourselves)s
     *
     * @return false|string
     *      is the registration not allowed? if not, returns string reason why not
     */
    public static function cannotRegister($event_id, $contact_id = null, $event_data = null) {
        if (empty($event_data)) {
            $event_data = CRM_Remoteevent_EventCache::getEvent($event_id);
        }

        // event active?
        if (empty($event_data['is_active'])) {
            return E::ts("Event is not active");
        }

        // registration within time frame?
        if (!empty($event_data['registration_start_date'])) {
            if (strtotime($event_data['registration_start_date']) > strtotime('now')) {
                return E::ts("Registration is not yet open.");
            }
        }
        if (!empty($event_data['registration_end_date'])) {
            if (strtotime($event_data['registration_end_date']) < strtotime('now')) {
                return E::ts("Registration has closed.");
            }
        }

        // check if max_participants set and NO waitlist:
        if (!empty($event_data['max_participants']) && empty($event_data['has_waitlist'])) {
            $registered_count = self::getRegistrationCount($event_id);
            if ($registered_count >= $event_data['max_participants']) {
                if (empty($event_data['event_full_text'])) {
                    return E::ts("Event is booked out");
                } else {
                    return $event_data['event_full_text'];
                }
            }
        }

        // check if this contact already registered
        // todo: if this is an invite only event, then we need instead see if there _is_ a pending contribution
        if ($contact_id) {
            $registered_count = self::getRegistrationCount($event_id, $contact_id, ['Positive', 'Pending']);
            if ($registered_count > 0) {
                return E::ts("Contact is already registered");
            }
        }

        // check whether the participant has been rejected, blocking a new registration
        $blacklist_status_list = Civi::settings()->get('remote_registration_blocking_status_list');
        if (!empty($blacklist_status_list) && is_array($blacklist_status_list)) {
            $blacklisted = self::getRegistrationCount($event_id, $contact_id, [], $blacklist_status_list);
            if ($blacklisted) {
                return E::ts("Contact already has a registration record and can currently not register.");
            }
        }

        // contact CAN register (can not not register)
        return false;
    }

    /**
     * Check if the given contact can edit a event registration
     *
     * @param integer $event_id
     *      the event you want to edit a registration for
     *
     * @param integer|null $contact_id
     *      the contact trying to edit the registration (in case of restricted registration)
     *
     * @param array $event_data
     *      the data known of the event (so we don't have to pull it ourselves)s
     *
     * @return false|string
     *      is the registration not allowed? if not, returns string reason why not
     */
    public static function cannotEditRegistration($event_id, $contact_id = null, $event_data = null) {
        if (empty($event_data)) {
            $event_data = CRM_Remoteevent_EventCache::getEvent($event_id);
        }

        // event active?
        if (empty($event_data['is_active'])) {
            return E::ts("Event is not active");
        }

        // todo: check if event has already started/ended?

        // is this allowed?
        if (empty($event_data['allow_selfcancelxfer'])) {
            return E::ts("Editing/cancelling registrations is not allowed for this event");
        }

        if ($contact_id) {
            // personalised stuff
            $active_registration = self::getActiveRegistration($event_id, $contact_id);
            if (empty($active_registration)) {
                return E::ts("No eligible registration for modification found.");
            }

            // check the timeframe
            if (!empty($event_data['selfcancelxfer_time'])) {
                $max_seconds_since_registration = 60 * 60 * (int) $event_data['selfcancelxfer_time'];
                $seconds_since_registration = strtotime($active_registration['register_date']);
                if ($seconds_since_registration > $max_seconds_since_registration) {
                    return E::ts("The window for registration changes has passed.");
                }
            }
        }

        // contact CAN edit registration (can not not edit)
        return false;
    }

    /**
     * Get the one participant object that is currently active/relevant
     *
     * @param integer $event_id
     * @param integer $contact_id
     *
     * @return array
     *   participant data
     */
    public static function getActiveRegistration($event_id, $contact_id)
    {
        // todo: cache/optimise?
        $event_id = (int) $event_id;
        $contact_id = (int) $contact_id;

        $candidates = [];
        $candidateQuery = "
            SELECT
             registration.contact_id       AS contact_id,
             registration.event_id         AS event_id,
             registration.id               AS registration_id,
             registration.register_date    AS register_date,
             registration.status_id        AS status_id,
             status.class                  AS status_class,
             registration.registered_by_id AS registered_by_id
            FROM civicrm_participant registration
            LEFT JOIN civicrm_participant_status_type status
                   ON status.id = registration.status_id
            WHERE registration.contact_id = {$contact_id}
              AND registration.event_id = {$event_id}
              AND (registration.is_test IS NULL OR registration.is_test = 0)
            ORDER BY registration.register_date DESC
        ";
        $candidateData = CRM_Core_DAO::executeQuery($candidateQuery);
        while ($candidateData->fetch()) {
            $candidates[] = [
                'contact_id'       => $candidateData->contact_id,
                'event_id'         => $candidateData->event_id,
                'registration_id'  => $candidateData->registration_id,
                'register_date'    => $candidateData->register_date,
                'status_id'        => $candidateData->status_id,
                'status_class'     => $candidateData->status_class,
                'registered_by_id' => $candidateData->registered_by_id,
            ];
        }

        // pick the most suitable one by class (and most recent registration)
        $search_order = ['Positive', 'Waiting', 'Pending'];
        foreach ($search_order as $status_class) {
            foreach ($candidates as $candidate) {
                if ($candidate['status_class'] == $status_class) {
                    return $candidate;
                }
            }
        }
        return null;
    }

    /**
     * Check if on-click registration is enabled for the event / the given contact
     *
     * @param integer $event_id
     *      the event you want to register to
     *
     * @param array $event_data
     *      the data known of the event (so we don't have to pull it ourselves)s
     *
     * @return true|string
     *      is the registration allowed? if not, returns reason why not
     */
    public static function canOneClickRegister($event_id, $event_data) {
        if (empty($event_data)) {
            $event_data = CRM_Remoteevent_RemoteEvent::getRemoteEvent($event_id);
        }

        // you can only do this, if the one-click registration is there as a profile
        if (!empty($event_data['enabled_profiles'])) {
            $enabled_profiles = explode(',', $event_data['enabled_profiles']);
            return in_array('OneClick', $enabled_profiles);
        } else {
            return false;
        }
    }

    /**
     * Get the count of current registrations for the given event
     *
     * @param integer $event_id
     *    event ID
     *
     * @param integer $contact_id
     *    restrict to this contact
     *
     * @param array $class_list
     *    list of participant status classes to be included - default is ony positive statuses
     *
     * @param array $status_id_list
     *    list of participant status ids to be included - default is <all>
     *
     * @return int
     *    number of registrations (participant objects)
     */
    public static function getRegistrationCount($event_id, $contact_id = null, $class_list = ['Positive'], $status_id_list = [])
    {
        $event_id = (int) $event_id;
        $contact_id = (int) $contact_id;

        // compile query
        $class_list = array_intersect(['Positive', 'Pending', 'Negative'], $class_list);
        if (empty($class_list)) {
            $REGISTRATION_CLASSES = "('Positive', 'Pending', 'Negative')";
        } else {
            $REGISTRATION_CLASSES = "('" . implode("','", $class_list) . "')";
        }
        if (empty($status_id_list)) {
            $AND_STATUS_ID_IN_LIST = "";
        } else {
            $status_id_list = array_map('intval', $status_id_list);
            $status_id_list = implode(',', $status_id_list);
            $AND_STATUS_ID_IN_LIST = "AND participant.status_id IN ({$status_id_list})";
        }
        if ($contact_id) {
            $AND_CONTACT_RESTRICTION = "AND participant.contact_id = {$contact_id}";
        } else {
            $AND_CONTACT_RESTRICTION = "";
        }

        $query = "
            SELECT COUNT(participant.id)
            FROM civicrm_participant participant
            LEFT JOIN civicrm_event  event
                   ON event.id = participant.event_id
            LEFT JOIN civicrm_participant_status_type status_type
                   ON status_type.id = participant.status_id
            WHERE status_type.class IN {$REGISTRATION_CLASSES}
                  AND participant.event_id = {$event_id}
                  {$AND_STATUS_ID_IN_LIST}
                  {$AND_CONTACT_RESTRICTION}";
        return (int) CRM_Core_DAO::singleValueQuery($query);
    }

    /**
     * Create or identify the contact based on the collected data
     *
     * @param RegistrationEvent $registration
     *      event triggered by the RemoteParticipant.submit
     */
    public static function createContactXCM($registration)
    {
        // get collected contact data
        $contact_identification = $registration->getContactData();

        // add contact type if it's missing
        if (empty($contact_identification['contact_type'])) {
            $contact_identification['contact_type'] = 'Individual';
        }

        if ($registration->getContactID()) {
            // the contact creation job has been done already -> check if want to do an upgrade
            $xcm_profile = Civi::settings()->get('remote_registration_xcm_profile_update');
            if ($xcm_profile) {
                // in this case we use the XCM with the update profile with the ID set
                $contact_identification['xcm_profile'] = $xcm_profile;
                $contact_identification['id'] = $registration->getContactID();
                civicrm_api3('Contact', 'getorcreate', $contact_identification);
            }

        } else {

            // this is a yet unidentified contact => run 'normal' xcm
            // add xcm profile, if one given
            if (empty($contact_identification['xcm_profile'])) {
                $xcm_profile = Civi::settings()->get('remote_registration_xcm_profile');
                if ($xcm_profile) {
                    $contact_identification['xcm_profile'] = $xcm_profile;
                }
            }

            // run through the contact matcher
            try {
                CRM_Remoteevent_CustomData::resolveCustomFields($contact_identification);
                $match = civicrm_api3('Contact', 'getorcreate', $contact_identification);
                $registration->setContactID($match['id']);
            } catch (Exception $ex) {
                throw new Exception(
                    E::ts("Not enough contact data to identify/create contact.")
                );
            }
        }
    }

    /**
     * Will identify a contact by its remote ID
     *
     * @param RegistrationEvent $registration
     *   registration event
     */
    public static function identifyRemoteContact($registration)
    {
        $contact_id = $registration->getContactID();
        if ($contact_id) {
            $registration->setContactID($contact_id);
        }
    }

    /**
     * Once the contact is identified, make sure that (s)he's personally eligible for registration
     *
     * @param RegistrationEvent $registration
     *   registration event
     */
    public static function verifyContactNotRegistered($registration)
    {
        if ($registration->getParticipantID()) {
            // there is already a registration identified
            return;
        }

        // now, after the contact has been identified, make sure (s)he's not already registered
        $cant_register_reason = CRM_Remoteevent_Registration::cannotRegister($registration->getEventID(), $registration->getContactID(), $registration->getEvent());
        if ($cant_register_reason) {
            $registration->addError($cant_register_reason);
        }
    }

    /**
     * If there is already an existing participant,
     *  process the confirmation
     *
     * @param RegistrationEvent $registration
     *   registration event
     */
    public static function confirmExistingParticipant($registration)
    {
        // of there is already an issue, don't waste any more time on this
        if ($registration->hasErrors()) {
            return;
        }

        $participant_id = $registration->getParticipantID();
        $submission = $registration->getSubmission();
        if (isset($submission['confirm'])) {
            if ($participant_id) {
                // there is already a (pre-existing) participant
                //   ... and the 'confirm' flag has been submitted
                //   then: update the participant right away
                $new_status = '';
                if (empty($submission['confirm'])) {
                    // participant want's out
                    $new_status = 'Rejected';
                } else {
                    // participant wants to confirm
                    if (CRM_Remoteevent_RemoteEvent::hasActiveWaitingList(
                        $registration->getEventID(),
                        $registration->getEvent()
                    )) {
                        $new_status = 'On waitlist';
                    } else {
                        $new_status = 'Registered';
                    }
                }

                // update participant right away
                civicrm_api3(
                    'Participant',
                    'create',
                    [
                        'id' => $participant_id,
                        'status_id' => $new_status
                    ]
                );
            } else {
                // there is no pre-existing participant, just add to the general to-be-created one
                if (empty($submission['confirm'])) {
                    $participant = &$registration->getParticipant();
                    $participant['status_id'] = 'Cancelled';
                }
            }
        }
    }



    /**
     * Will calculate the participant status
     *
     * @param RegistrationEvent $registration
     *   registration event
     */
    public static function determineParticipantStatus($registration)
    {
        // of there is already an issue, don't waste any more time on this
        if ($registration->hasErrors()) {
            return;
        }

        if ($registration->getParticipantID()) {
            // there is already a registration identified
            return;
        }

        // default status calculation
        $participant_data = &$registration->getParticipant();
        $event_data = $registration->getEvent();

        // check if it registration requires approval
        if (empty($participant_data['participant_status_id'])) {
            if (!empty($event_data['requires_approval'])) {
                // there is an active waiting list, see if need to get on it
                $participant_data['participant_status_id'] = 'Awaiting approval';
            }
        }

        // check if this has a waiting list
        if (empty($participant_data['participant_status_id'])) {
            if (CRM_Remoteevent_RemoteEvent::hasActiveWaitingList($event_data['id'], $event_data)) {
                $participant_data['participant_status_id'] = 'On waitlist';

                if (!empty($event_data['waitlist_text'])) {
                    $registration->addError($event_data['waitlist_text']);
                } else {
                    $registration->addError(E::ts("You have been added to the waitlist."));
                }
            }
        }

        // finally: the default status is Registered
        if (empty($participant_data['participant_status_id'])) {
            $participant_data['participant_status_id'] = 'Registered';
        }
    }


    /**
     * Will create a simple participant object
     *
     * @param RegistrationEvent $registration
     *   registration event
     */
    public static function createParticipant($registration)
    {
        if ($registration->getParticipantID()) {
            // someone else has done it
            return;
        }

        // of there is already an issue, don't waste any more time on this
        if ($registration->hasErrors()) {
            return;
        }

        // our job: create a simple participant
        $participant_data = &$registration->getParticipant();
        $participant_data['contact_id'] = $registration->getContactID();
        CRM_Remoteevent_CustomData::resolveCustomFields($participant_data);
        $creation = civicrm_api3('Participant', 'create', $participant_data);
        $participant = civicrm_api3('Participant', 'getsingle', ['id' => $creation['id']]);
        CRM_Remoteevent_CustomData::labelCustomFields($participant);
        $registration->setParticipant($participant);

        // invalidate caches
        self::invalidateRegistrationCache($participant_data['contact_id'], $participant_data['event_id']);
        CRM_Remoteevent_RemoteEvent::invalidateRemoteEvent($registration->getEventID());
    }

    /**
     * Get a (cached version) of ParticipantStatusType.get
     */
    public static function getParticipantStatusList()
    {
        static $status_list = null;
        if ($status_list === null) {
            $status_list = [];
            $query = civicrm_api3('ParticipantStatusType', 'get', ['option.limit' => 0]);
            foreach ($query['values'] as $status) {
                $status_list[$status['id']] = $status;
            }
        }
        return $status_list;
    }

    /**
     * Get a the class of the given status ID
     *
     * @param integer $participant_status_id
     *   the status id
     *
     * @return string
     *   class name: 'Positive', 'Negative', 'Pending'...
     */
    public static function getParticipantStatusClass($participant_status_id)
    {
        $status_list = self::getParticipantStatusList();
        $status = $status_list[$participant_status_id];
        return $status['class'];
    }

    /**
     * Add the GTAC data to the get_form results
     *
     * @param GetCreateParticipantFormEvent $get_form_results
     *      event triggered by the RemoteParticipant.get_form API call
     */
    public static function addGtacField($get_form_results) {
        $event = $get_form_results->getEvent();
        if (!empty($event['event_remote_registration.remote_registration_gtac'])) {
            $l10n = $get_form_results->getLocalisation();
            $get_form_results->addFields([
                'gtac' => [
                    'name' => 'gtac',
                    'type' => 'Checkbox',
                    'validation' => '',
                    'weight' => 500,
                    'required' => 1,
                    'label' => $l10n->localise("I accept the following terms and conditions"),
                    'description' => $l10n->localise("You have to accept the terms and conditions to participate in this event"),
                    'group_name' => 'gtacs',
                    'group_label' => $l10n->localise("Terms and Conditions"),
                    'prefix' => '',
                    'suffix' => $event['event_remote_registration.remote_registration_gtac'],
                    'prefix_display' => '',
                    'suffix_display' => 'dialog'
                ]
            ]);
        }
    }
}
