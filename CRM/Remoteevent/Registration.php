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

use Civi\Api4\Participant;
use Civi\RemoteParticipant\Event\GetCreateParticipantFormEvent;
use Civi\RemoteParticipant\Event\RegistrationEvent;
use Civi\RemoteParticipant\Event\UpdateParticipantEvent;
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Class to execute event registrations (RemoteParticipant.create)
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

    const PARTICIPANT_FIELDS = ['id','contact_id','event_id','participant_status_id','participant_role_id'];

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
                'return'       => implode(',', self::PARTICIPANT_FIELDS)
            ];
            if (!empty($event_ids)) {
                $participant_params['event_id'] = ['IN' => $event_ids];
            }
            $participant_query = civicrm_api3('Participant', 'get', $participant_params);
            foreach ($participant_query['values'] as $participant_found) {
                $participant_data = [];
                foreach (self::PARTICIPANT_FIELDS as $field_name) {
                    $participant_data[$field_name] = CRM_Utils_Array::value($field_name, $participant_found, '');
                    // shouldn't be needed
                    //if (substr($field_name, 0, 12) == 'participant_') {
                    //    $participant_data[substr($field_name, 12)] = $participant_data[$field_name];
                    //}
                }
                self::$cached_registration_data[$contact_id][] = $participant_data;
            }
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

        if ($event_data['event_remote_registration.require_user_account'] && $contact_id === null) {
            return E::ts('You need to be logged in to register for this event.');
        }

        // registration suspended?
        if (self::isRegistrationSuspended($event_id, $event_data)) {
            return E::ts("Registration Deactivated");
        }

        // event active?
        if (empty($event_data['is_active'])) {
            return E::ts("Event is not active");
        }

        // event passed?
        if (!empty($event_data['end_date'])) {
            if (strtotime($event_data['end_date']) < strtotime('now')) {
                return E::ts("Event has ended.");
            }
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
            $registered_count = self::getRegistrationCount($event_id, $contact_id);
            if ($registered_count > 0) {
                return E::ts("Contact is already registered");
            }
        }

        // check whether the participant has been rejected, blocking a new registration
        if ($contact_id) {
            $blacklist_status_list = Civi::settings()->get('remote_registration_blocking_status_list');
            if (!empty($blacklist_status_list) && is_array($blacklist_status_list)) {
                $blacklisted = self::getRegistrationCount($event_id, $contact_id, [], $blacklist_status_list, false);
                if ($blacklisted) {
                    return E::ts("Contact already has a registration record and can currently not register.");
                }
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

        // check the timeframe
        if (!self::cancellationStillAllowed($event_data)) {
            return E::ts("The window for registration changes has passed.");
        }

        if ($contact_id) {
            // personalised stuff
            $active_registration = self::getActiveRegistration($event_id, $contact_id);
            if (empty($active_registration)) {
                return E::ts("No eligible registration for modification found.");
            }
        }

        if (empty($event_data['enabled_update_profiles'])) {
            return E::ts("This event does not allow participants to update their registration.");
        }

        // contact CAN edit registration (can not not edit)
        return false;
    }

    /**
     * Check whether the setting for 'selfcancelxfer_time' currently
     *   allows cancellations. This will *not* check if they are generally
     *   enabled or the participant has the permission to do so.
     *
     * @param array $event_data
     */
    public static function cancellationStillAllowed($event_data)
    {
        if (empty($event_data['selfcancelxfer_time'])) {
            return true; // no restrictions
        } else {
            $min_seconds_before_start = 60 * 60 * (int) $event_data['selfcancelxfer_time'];
            $current_seconds_before_start = strtotime($event_data['start_date']) - strtotime('now');
            if ($current_seconds_before_start > $min_seconds_before_start) {
                return true; // we can still cancel
            } else {
                return false; // we're too close to the event starting date
            }
        }
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
     * Check the registration for this event is currently suspended (setting)
     *
     * @param integer $event_id
     *      the event you want to register to
     *
     * @param array $event_data
     *      the data known of the event (so we don't have to pull it ourselves)s
     *
     * @return true|string
     *      is the registration currently suspended?
     */
    public static function isRegistrationSuspended($event_id, $event_data) {
        if (empty($event_data)) {
            $event_data = CRM_Remoteevent_RemoteEvent::getRemoteEvent($event_id);
        }

        return !empty($event_data['event_remote_registration.remote_registration_suspended']);
    }

  /**
   * Retrieves the count of current registrations for the given event.
   *
   * @param int $event_id
   *   event ID
   *
   * @param int $contact_id
   *   restrict to this contact
   *
   * @param array $class_list
   *   list of participant status classes to be included - default is ony positive statuses
   *
   * @param array $status_id_list
   *   list of participant status ids to be included - default is <all>
   *
   * @param bool $only_counted
   *   only count registration where the status_type has is_counted = 1
   *
   * @return int
   *   number of registrations (participant objects)
   */
  public static function getRegistrationCount(
    $event_id,
    $contact_id = NULL,
    array $class_list = [],
    array $status_id_list = [],
    bool $only_counted = TRUE
  ): int {
    $event_id = (int) $event_id;
    $contact_id = (int) $contact_id;

    // compile query
    $class_list = array_intersect(['Positive', 'Pending', 'Negative', 'Waiting'], $class_list);
    if ([] === $class_list) {
      $REGISTRATION_CLASSES = "('Positive', 'Pending', 'Negative', 'Waiting')";
    }
    else {
      $REGISTRATION_CLASSES = "('" . implode("','", $class_list) . "')";
    }
    if ([] === $status_id_list) {
      $AND_STATUS_ID_IN_LIST = '';
    }
    else {
      $status_id_list = array_map('intval', $status_id_list);
      $status_id_list = implode(',', $status_id_list);
      $AND_STATUS_ID_IN_LIST = "AND participant.status_id IN ({$status_id_list})";
    }
    if ($contact_id) {
      $AND_CONTACT_RESTRICTION = "AND participant.contact_id = {$contact_id}";
    }
    else {
      $AND_CONTACT_RESTRICTION = '';
    }
    if ($only_counted) {
      $AND_IS_COUNTED_CONDITION =
        'AND status_type.is_counted = 1 AND option_value_participant_role.filter = 1';
    }
    else {
      $AND_IS_COUNTED_CONDITION = '';
    }

    $value_separator = CRM_Core_DAO::VALUE_SEPARATOR;
    // phpcs:disable Generic.Files.LineLength.TooLong
    $query = <<<SQL
      SELECT
        IF(
          -- If the line item count * the line item quantity is not 0
          SUM(price_field_value.`count` * lineItem.qty),

          -- then use the count * the quantity, ensuring each
          -- actual participant record gets a result
          SUM(price_field_value.`count` * lineItem.qty)
            + COUNT(DISTINCT participant.id)
            - COUNT(DISTINCT IF (price_field_value.`count`, participant.id, NULL)),

          -- if the line item count is NULL or 0 then count the participants
          COUNT(DISTINCT participant.id)
        )
      FROM civicrm_participant participant
      LEFT JOIN civicrm_event event
        ON event.id = participant.event_id
      LEFT JOIN civicrm_participant_status_type status_type
        ON status_type.id = participant.status_id
      LEFT JOIN civicrm_line_item lineItem
        ON
          lineItem.entity_id = participant.id
          AND  lineItem.entity_table = 'civicrm_participant'
      LEFT JOIN civicrm_price_field_value price_field_value
        ON
          price_field_value.id = lineItem.price_field_value_id
          AND price_field_value.`count`
      INNER JOIN civicrm_option_value option_value_participant_role
        ON
          participant.role_id = option_value_participant_role.value
          OR participant.role_id LIKE CONCAT('%', option_value_participant_role.value, '{$value_separator}%')
          OR participant.role_id LIKE CONCAT('%{$value_separator}', option_value_participant_role.value, '%')
          OR participant.role_id LIKE CONCAT('%{$value_separator}', option_value_participant_role.value, '{$value_separator}%')
      INNER JOIN civicrm_option_group option_group_participant_role
        ON
          option_group_participant_role.id = option_value_participant_role.option_group_id
          AND option_group_participant_role.name = 'participant_role'
      WHERE status_type.class IN {$REGISTRATION_CLASSES}
        {$AND_IS_COUNTED_CONDITION}
        AND participant.event_id = {$event_id}
        {$AND_STATUS_ID_IN_LIST}
        {$AND_CONTACT_RESTRICTION}
      SQL;
    // phpcs:enable
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

        if (!$registration->isContactUpdated()) {
            if ($registration->getContactID()) {
                // in this case we use the XCM with the update profile with the ID set
                $contact_identification['id'] = $registration->getContactID();
            }

            try {
                // run through the contact matcher
                $contact_identification['xcm_profile'] = $registration->getXcmMatchProfile();
                CRM_Remoteevent_CustomData::resolveCustomFields($contact_identification);
                $match = civicrm_api3('Contact', 'getorcreate', $contact_identification);

            } catch (Exception $ex) {
                if (empty($contact_identification['id'])) {
                    // no contact ID given -> there must be some data missing
                    throw new Exception(
                        E::ts("Couldn't find or create contact: ") . $ex->getMessage());

                } else {
                    // the contact ID ws passed, but it still failed.
                    // first: check if options.match_contact_id is set
                    $profile_name = $contact_identification['xcm_profile'];
                    $xcm_config = CRM_Xcm_Configuration::getConfigProfile($profile_name);
                    if (empty($xcm_config) || empty($xcm_config->getOptions()['match_contact_id'])) {
                        Civi::log()->warning("RemoteEvent: maybe you should activate the 'Match contacts by contact ID' option for your '{$profile_name}' profile, so that contact updates could also be applied if the participant is only identified by ID.");
                    } else {
                        throw new Exception(E::ts("Error while updating contact %1: %2", [
                            1 => $contact_identification['id'],
                            2 => $ex->getMessage()]));
                    }
                }
            }

            // make sure a badly configured xcm call doesn't change the current contact
            if (!$registration->getContactID()) {
                $registration->setContactID($match['id']);
            }
            $registration->setContactUpdated();
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
                // mark as updated to avoid additional calls
                // see also: https://github.com/systopia/de.systopia.remoteevent/issues/19
                $registration->setParticipantUpdated();
            } else {
                // there is no pre-existing participant, just add to the general to-be-created one
                if (empty($submission['confirm'])) {
                    $participant = &$registration->getParticipantData();
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
        $participant_data = &$registration->getParticipantData();
        $event_data = $registration->getEvent();

        // check if this has a waiting list
        if (empty($participant_data['participant_status_id'])) {
            if (CRM_Remoteevent_RemoteEvent::hasActiveWaitingList($event_data['id'], $event_data)) {
                $participant_data['participant_status_id'] = 'On waitlist';

                if (!empty($event_data['waitlist_text'])) {
                    $registration->addStatus($event_data['waitlist_text']);
                } else {
                    $registration->addStatus(E::ts("You have been added to the waitlist."));
                }
            }
        }

        // check if it registration requires approval
        if (empty($participant_data['participant_status_id'])) {
            if (!empty($event_data['requires_approval'])) {
                // there is an active waiting list, see if need to get on it
                $participant_data['participant_status_id'] = 'Awaiting approval';
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
        // of there is already an issue, don't waste any more time on this
        if ($registration->hasErrors()) {
            return;
        }

        // let's look into this
        $participant_data = &$registration->getParticipantData();

        if ($registration->getParticipantID()) {
            // this is updating an existing participant
            $participant_data['id'] = $registration->getParticipantID();
            if (!$registration->isParticipantUpdated()) {
                $participant_data['force_trigger_eventmessage'] = 1;
            }

        } else {
            // we're creating an all new participant
            if (!isset($participant_data['contact_id'])) {
                $participant_data['contact_id'] = $registration->getContactID();
            }
        }

        // Modify Participant Data Event here. This can be used to maybe manually update/set participant data
        $update_participant_event = new UpdateParticipantEvent($participant_data);
        // dispatch Registration Profile Event and try to instantiate a profile class from $profile_name
        Civi::dispatcher()->dispatch(UpdateParticipantEvent::NAME, $update_participant_event);
        $participant_data = $update_participant_event->get_participant_data();

        // run create/update
        CRM_Remoteevent_CustomData::resolveCustomFields($participant_data);
        $creation = civicrm_api3('Participant', 'create', $participant_data);
        $registration->setParticipantUpdated();
        $participant = civicrm_api3('Participant', 'getsingle', ['id' => $creation['id']]);
        CRM_Remoteevent_CustomData::labelCustomFields($participant);
        $registration->setParticipant($participant);

        // invalidate caches
        self::invalidateRegistrationCache($participant_data['contact_id'], $participant_data['event_id']);
        CRM_Remoteevent_RemoteEvent::invalidateRemoteEvent($registration->getEventID());
    }

    public static function registerAdditionalParticipants(RegistrationEvent $registration): void {
        if ($registration->hasErrors() || [] === $registration->getAdditionalParticipantsData()) {
           return;
        }

        $additionalContactsData = $registration->getAdditionalContactsData();
        $additionalParticipantsData = $registration->getAdditionalParticipantsData();

        foreach ($additionalContactsData as $participantNo => $contactData) {
            CRM_Remoteevent_CustomData::resolveCustomFields($contactData);
            $match = civicrm_api3('Contact', 'getorcreate', $contactData);
            if (!isset($match['id'])) {
               throw new \RuntimeException('Contact for additional participant could not be identified or created.');
            }

            $additionalParticipantsData[$participantNo]['contact_id']
              = $additionalContactsData[$participantNo]['id']
              = $contactData['id'] = $match['id'];

            // Check for existing participants for the identified contact.
            $cannotRegisterReason = CRM_Remoteevent_Registration::cannotRegister(
                $registration->getEventID(),
                $contactData['id'],
                $registration->getEvent()
            );
            if ($cannotRegisterReason) {
                $registration->addError(
                    E::ts('Additional participant %1: %2', [
                        1 => $participantNo,
                        2 => $cannotRegisterReason,
                    ])
                );
          }
          // Do not register participants yet, as there might be reasons for not
          // registering, and we want to collect all of them first.
        }

        $registration->setAdditionalContactsData($additionalContactsData);

        // Abort if any additional participant can't be registered.
        if ($registration->hasErrors()) {
            return;
        }

        // Create additional participants.
        foreach ($additionalParticipantsData as &$participantData) {
            $participantData['registered_by_id'] = $registration->getParticipantID();
            $participantData['register_date'] = date('Y-m-d H:i');
            $participantRegistered = Participant::create(false)
                ->setValues($participantData)
                ->execute()
                ->single();

            $participantData += $participantRegistered;
        }

        $registration->setAdditionalParticipantsData($additionalParticipantsData);
    }

    public static function createOrder(RegistrationEvent $registration): void {
      $event = $registration->getEvent();
      if ((bool) $event['is_monetary']) {
        $order = \Civi\Api4\Order::create(FALSE)
          ->setContributionValues([
            'contact_id' => $registration->getContactID(),
            'financial_type_id' => (int) $event['financial_type_id'],
            'payment_instrument_id' => static::getPaymentInstrumentForPaymentMethod(
              $registration->getSubmittedValue('payment_method'),
              $event
            )
          ]);
        foreach ($registration->getPriceFieldValues() as $value) {
          $order->addLineItem([
            'entity_table' => 'civicrm_participant',
            'entity_id' => $value['participant_id'],
            'price_field_id' => $value['price_field_id'],
            'price_field_value_id' => $value['price_field_value_id'],
            'qty' => $value['qty'],
          ]);
        }
        try {
          $orderResult = $order
            ->execute()
            ->single();
          $registration->setOrderData($orderResult);
        }
        catch (CRM_Core_Exception $exception) {
          $registration->addError(E::ts('Could not register order.'));
        }
      }
    }

  /**
   * @phpstan-param array{
   *   id: int,
   *   currency: string,
   *   fee_label: string,
   *   is_monetary: int,
   * } $event
   */
    public static function getPaymentInstrumentForPaymentMethod(string $paymentMethod, array $event): int {
      switch ($paymentMethod) {
        case 'pay_later':
          // TODO: Make payment method for "Pay later" configurable per event.
          return (int) \Civi\Api4\OptionValue::get(FALSE)
            ->addSelect('value')
            ->addWhere('option_group_id.name', '=', 'payment_instrument')
            ->addWhere('name', '=', 'EFT')
            ->execute()['value'];

        case 'sepa':
          // Use OOFF payment instrument from creditor.
          // TODO: Make creditor configurable per event.
          return (int) \CRM_Sepa_Logic_Settings::defaultCreditor()->pi_ooff;
      }
    }

    public static function createPayment(RegistrationEvent $registration): void {
      $event = $registration->getEvent();
      if ((bool) $event['is_monetary']) {
        $order = $registration->getOrderData();

        switch ($registration->getSubmittedValue('payment_method')) {
          case 'pay_later':
            // Nothing to do here, there is already a pending contribution.
            break;

          case 'sepa':
            $mandate = \Civi\Api4\SepaMandate::create(FALSE)
              ->addValue('creditor_id', NULL)
              ->addValue('type', 'OOFF')
              ->addValue('iban', $registration->getSubmittedValue('payment_method_sepa_iban'))
              ->addValue('bic', $registration->getSubmittedValue('payment_method_sepa_bic'))
              ->addValue('status', 'OOFF')
              // Reference is given a default when empty(), but is a required field, thus passing 0.
              // TODO: Adjust CiviSEPA to not make the API parameter required if there is a default, and do not pass a
              //       a value once that's done.
              ->addValue('reference', 0)
              ->addValue('entity_table', 'civicrm_contribution')
              ->addValue('entity_id', $order['id'])
              ->addValue('contact_id', $registration->getContactID())
              ->addValue('currency', $event['currency'])
              ->addValue('date', $order['receive_date'])
              ->addValue('creation_date', $order['receive_date'])
              ->addValue('validation_date', $order['receive_date'])
              ->execute();
            break;

          default:
            // TODO: Register submitted payment (API to be defined; must include the amount and the date, possibly a
            //       transaction ID, etc.) using \Civi\Api4\Payment::create().
            break;
        }
      }
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
     * Get a the name of the given status ID
     *
     * @param integer $participant_status_id
     *   the status id
     *
     * @return string
     *   status (internal) name: 'Registered', 'Attended', ...
     */
    public static function getParticipantStatusName($participant_status_id)
    {
        $status_list = self::getParticipantStatusList();
        $status = $status_list[$participant_status_id];
        return $status['name'];
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
                'gtacs' => [
                    'type'        => 'fieldset',
                    'name'        => 'gtacs',
                    'label'       => $l10n->ts("General Terms and Conditions"),
                    'weight'      => 500, // this should be at the end
                ],
                'gtac' => [
                    'name' => 'gtac',
                    'type' => 'Checkbox',
                    'validation' => '',
                    'weight' => 100,
                    'required' => 1,
                    'label' => $l10n->ts("I accept the following terms and conditions"),
                    'description' => '', //$l10n->ts("You have to accept the terms and conditions to participate in this event"),
                    'parent' => 'gtacs',
                    'suffix' => $event['event_remote_registration.remote_registration_gtac'],
                    'suffix_display' => 'inline',
                    'suffix_dialog_label' => $l10n->ts("Details"),
                ]
            ]);
        }
    }

}
