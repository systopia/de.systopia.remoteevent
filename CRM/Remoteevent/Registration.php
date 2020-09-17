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

/**
 * Class to coordinate event registrations (RemoteParticipant)
 */
class CRM_Remoteevent_Registration
{
    const STAGE1_CONTACT_IDENTIFICATION = 500;
    const STAGE2_PARTICIPANT_CREATION   = 0;
    const STAGE3_POSTPROCESSING         = -500;
    const STAGE4_COMMUNICATION          = -1000;

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

            $participant_query = civicrm_api3('Participant', 'get', [
                'contact_id'   => $contact_id,
                'event_id'     => ['IN' => $event_ids],
                'option.limit' => 0,
                'return'       => 'id,event_id,contact_id,role,status', // todo: more?
                'sequential'   => 1,
            ]);
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
            $event_data = CRM_Remoteevent_RemoteEvent::getRemoteEvent($event_id);
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
                return E::ts("Event is full");
            }
        }

        // check if this contact already registered
        // todo: if this is an invite only event, then we need instead see if there _is_ a pending contribution
        if ($contact_id) {
            $registered_count = self::getRegistrationCount($event_id, true, $contact_id);
            if ($registered_count > 0) {
                return E::ts("Contact is already registered");
            }
        }

        // contact CAN register (can not not register)
        return false;
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
     * @param boolean $count_pending
     *    also count pending registrations?
     *
     * @return int
     *    number of registrations (participant objects)
     */
    public static function getRegistrationCount($event_id, $count_pending = false, $contact_id = null)
    {
        $event_id = (int) $event_id;
        $contact_id = (int) $contact_id;
        if ($count_pending) {
            $REGISTRATION_CLASSES = "('Positive', 'Pending')";
        } else {
            $REGISTRATION_CLASSES = "('Positive')";
        }
        if ($contact_id) {
            $AND_CONTACT_RESTRICTION = "participant.contact_id = {$contact_id}";
        } else {
            $AND_CONTACT_RESTRICTION = "";
        }

        $query = "
            SELECT COUNT(participant.id)
            FROM civicrm_participant participant
            LEFT JOIN civicrm_event  event
                   ON event.id = {$event_id}
            LEFT JOIN civicrm_participant_status_type status_type
                   ON status_type.id = participant.status_id
            WHERE status_type.class IN {$REGISTRATION_CLASSES}
                  {$AND_CONTACT_RESTRICTION}";
        return (int) CRM_Core_DAO::singleValueQuery($query);
    }

    /**
     * Will identify a contact by its remote ID
     *
     * @param RegistrationEvent $registration
     *   registration event
     */
    public static function identifyRemoteContact($registration)
    {
        if (!$registration->getContactID() && !empty($registration->getSubmittedValue('remote_contact_id'))) {
            $contact_id = CRM_Remotetools_Contact::getByKey($registration->getSubmittedValue('remote_contact_id'));
            if ($contact_id) {
                $registration->setContactID($contact_id);
            }
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

        // create a simple participant
        $creation = civicrm_api3('Participant', 'create', [
            'contact_id' => $registration->getContactID(),
            'event_id'   => $registration->getEventID()
        ]);
        $participant = civicrm_api3('Participant', 'getsingle', ['id' => $creation['id']]);
        $registration->setParticipant($participant);
    }

}
