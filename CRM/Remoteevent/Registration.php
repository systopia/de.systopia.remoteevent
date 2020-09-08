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
     * @param integer $event_id
     *      the event you want to register to
     *
     * @param integer|null $contact_id
     *      the contact trying to register (in case of restricted registration)
     *
     * @return boolean
     *      is the registration allowed
     */
    public static function canRegister($event_id, $contact_id = null) {
        // todo: check event status, availability, date, etc.

        return true;
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
