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

declare(strict_types = 1);

use Civi\Api4\Participant;
use Civi\RemoteParticipant\Event\RegistrationEvent;
use Civi\RemoteParticipant\Event\UpdateParticipantEvent;
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Class to execute event registrations (RemoteParticipant.create)
 */
class CRM_Remoteevent_Registration {
  public const STAGE1_CONTACT_IDENTIFICATION = 5000;
  public const STAGE2_PARTICIPANT_CREATION   = 0;
  public const STAGE3_POSTPROCESSING         = -5000;
  public const STAGE4_COMMUNICATION          = -10000;

  public const BEFORE_CONTACT_IDENTIFICATION = self::STAGE1_CONTACT_IDENTIFICATION + 50;
  public const AFTER_CONTACT_IDENTIFICATION  = self::STAGE1_CONTACT_IDENTIFICATION - 50;
  public const BEFORE_PARTICIPANT_CREATION   = self::STAGE2_PARTICIPANT_CREATION + 50;
  public const AFTER_PARTICIPANT_CREATION    = self::STAGE2_PARTICIPANT_CREATION - 50;

  public const PARTICIPANT_FIELDS = ['id', 'contact_id', 'event_id', 'participant_status_id', 'participant_role_id'];

  /**
   * @var array list of [contact_id -> list of participant data] */
  protected static $cached_registration_data = [];

  /**
   * @var array list of event_ids */
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
  public static function cacheRegistrationData($event_ids, $contact_id) {
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
        'return'       => implode(',', self::PARTICIPANT_FIELDS),
      ];
      if (!empty($event_ids)) {
        $participant_params['event_id'] = ['IN' => $event_ids];
      }
      $participant_query = civicrm_api3('Participant', 'get', $participant_params);
      foreach ($participant_query['values'] as $participant_found) {
        $participant_data = [];
        foreach (self::PARTICIPANT_FIELDS as $field_name) {
          $participant_data[$field_name] = $participant_found[$field_name] ?? '';
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
   *   list of participants
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
   */
  public static function invalidateRegistrationCache($contact_id, $event_id) {
    // unset the registration data
    unset(self::$cached_registration_data[$contact_id]);

    // update the indicator
    $event_cached_key = array_search($event_id, self::$cached_registration_event_ids);
    if ($event_cached_key !== FALSE) {
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
   *   is the registration not allowed? if not, returns string reason why not
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public static function cannotRegister($event_id, $contact_id = NULL, $event_data = NULL) {
    if (empty($event_data)) {
      $event_data = CRM_Remoteevent_EventCache::getEvent($event_id);
    }

    if ($event_data['event_remote_registration.require_user_account'] && $contact_id === NULL) {
      return E::ts('You need to be logged in to register for this event.');
    }

    // registration suspended?
    if (self::isRegistrationSuspended($event_id, $event_data)) {
      return E::ts('Registration Deactivated');
    }

    // event active?
    if (empty($event_data['is_active'])) {
      return E::ts('Event is not active');
    }

    // event passed?
    if (!empty($event_data['end_date'])) {
      if (strtotime($event_data['end_date']) < strtotime('now')) {
        return E::ts('Event has ended.');
      }
    }

    // registration within time frame?
    if (!empty($event_data['registration_start_date'])) {
      if (strtotime($event_data['registration_start_date']) > strtotime('now')) {
        return E::ts('Registration is not yet open.');
      }
    }
    if (!empty($event_data['registration_end_date'])) {
      if (strtotime($event_data['registration_end_date']) < strtotime('now')) {
        return E::ts('Registration has closed.');
      }
    }

    // check if max_participants set and NO waitlist:
    if (!empty($event_data['max_participants']) && empty($event_data['has_waitlist'])) {
      $registered_count = self::getRegistrationCount($event_id);
      if ($registered_count >= $event_data['max_participants']) {
        if (empty($event_data['event_full_text'])) {
          return E::ts('Event is booked out');
        }
        else {
          return $event_data['event_full_text'];
        }
      }
    }

    // check if this contact already registered
    // todo: if this is an invite only event, then we need instead see if there _is_ a pending contribution
    if ($contact_id) {
      $registered_count = self::getRegistrationCount($event_id, $contact_id);
      if ($registered_count > 0) {
        return E::ts('Contact is already registered');
      }
    }

    // check whether the participant has been rejected, blocking a new registration
    if ($contact_id) {
      $blacklist_status_list = Civi::settings()->get('remote_registration_blocking_status_list');
      if (!empty($blacklist_status_list) && is_array($blacklist_status_list)) {
        $blacklisted = self::getRegistrationCount($event_id, $contact_id, [], $blacklist_status_list, FALSE);
        if ($blacklisted) {
          return E::ts('Contact already has a registration record and can currently not register.');
        }
      }
    }

    // contact CAN register (can not not register)
    return FALSE;
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
   *   is the registration not allowed? if not, returns string reason why not
   */
  public static function cannotEditRegistration($event_id, $contact_id = NULL, $event_data = NULL) {
    if (empty($event_data)) {
      $event_data = CRM_Remoteevent_EventCache::getEvent($event_id);
    }

    // event active?
    if (empty($event_data['is_active'])) {
      return E::ts('Event is not active');
    }

    // todo: check if event has already started/ended?

    // is this allowed?
    if (empty($event_data['allow_selfcancelxfer'])) {
      return E::ts('Editing/cancelling registrations is not allowed for this event');
    }

    // check the timeframe
    if (!self::cancellationStillAllowed($event_data)) {
      return E::ts('The window for registration changes has passed.');
    }

    if ($contact_id) {
      // personalised stuff
      $active_registration = self::getActiveRegistration($event_id, $contact_id);
      if (empty($active_registration)) {
        return E::ts('No eligible registration for modification found.');
      }
    }

    if (empty($event_data['enabled_update_profiles'])) {
      return E::ts('This event does not allow participants to update their registration.');
    }

    // contact CAN edit registration (can not not edit)
    return FALSE;
  }

  /**
   * Check whether the setting for 'selfcancelxfer_time' currently
   *   allows cancellations. This will *not* check if they are generally
   *   enabled or the participant has the permission to do so.
   *
   * @param array $event_data
   */
  public static function cancellationStillAllowed($event_data) {
    if (empty($event_data['selfcancelxfer_time'])) {
      // no restrictions
      return TRUE;
    }
    else {
      $min_seconds_before_start = 60 * 60 * (int) $event_data['selfcancelxfer_time'];
      $current_seconds_before_start = strtotime($event_data['start_date']) - strtotime('now');
      if ($current_seconds_before_start > $min_seconds_before_start) {
        // we can still cancel
        return TRUE;
      }
      else {
        // we're too close to the event starting date
        return FALSE;
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
  public static function getActiveRegistration($event_id, $contact_id) {
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
    return NULL;
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
   *   is the registration allowed? if not, returns reason why not
   */
  public static function canOneClickRegister($event_id, $event_data) {
    if (empty($event_data)) {
      $event_data = CRM_Remoteevent_RemoteEvent::getRemoteEvent($event_id);
    }

    // you can only do this, if the one-click registration is there as a profile
    if (!empty($event_data['enabled_profiles'])) {
      $enabled_profiles = explode(',', $event_data['enabled_profiles']);
      return in_array('OneClick', $enabled_profiles);
    }
    else {
      return FALSE;
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
   *   is the registration currently suspended?
   */
  public static function isRegistrationSuspended($event_id, $event_data) {
    if (empty($event_data)) {
      $event_data = CRM_Remoteevent_RemoteEvent::getRemoteEvent($event_id);
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

    // TODO: Include price options with participant count > 1.

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
            + COUNT(DISTINCT participant.id )
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

      }
      catch (Exception $ex) {
        if (empty($contact_identification['id'])) {
          // no contact ID given -> there must be some data missing
          throw new Exception(
          E::ts("Couldn't find or create contact: ") . $ex->getMessage());

        }
        else {
          // the contact ID ws passed, but it still failed.
          // first: check if options.match_contact_id is set
          $profile_name = $contact_identification['xcm_profile'];
          $xcm_config = CRM_Xcm_Configuration::getConfigProfile($profile_name);
          if (empty($xcm_config) || empty($xcm_config->getOptions()['match_contact_id'])) {
            Civi::log()->warning(
              // phpcs:ignore Generic.Files.LineLength.TooLong
              "RemoteEvent: maybe you should activate the 'Match contacts by contact ID' option for your '{$profile_name}' profile, so that contact updates could also be applied if the participant is only identified by ID."
            );
          }
          else {
            throw new Exception(E::ts('Error while updating contact %1: %2', [
              1 => $contact_identification['id'],
              2 => $ex->getMessage(),
            ]));
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
   * @param \Civi\RemoteParticipant\Event\RegistrationEvent $registration
   *   registration event
   */
  public static function identifyRemoteContact($registration) {
    $contact_id = $registration->getContactID();
    if ($contact_id) {
      $registration->setContactID($contact_id);
    }
  }

  /**
   * Once the contact is identified, make sure that (s)he's personally eligible for registration
   *
   * @param \Civi\RemoteParticipant\Event\RegistrationEvent $registration
   *   registration event
   */
  public static function verifyContactNotRegistered($registration) {
    if ($registration->getParticipantID()) {
      // there is already a registration identified
      return;
    }

    public static function createOrder(RegistrationEvent $registration): void {
      $event = $registration->getEvent();
      if (
        (bool) $event['is_monetary']
        && class_exists('\Civi\Api4\Order')
      ) {
        $order = \Civi\Api4\Order::create(FALSE)
          ->setContributionValues([
            'contact_id' => $registration->getContactID(),
            'financial_type_id' => $event['financial_type_id'],
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
        $order->execute();
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
        else {
          // participant wants to confirm
          if (CRM_Remoteevent_RemoteEvent::hasActiveWaitingList(
                $registration->getEventID(),
                $registration->getEvent()
            )) {
            $new_status = 'On waitlist';
          }
          else {
            $new_status = 'Registered';
          }
        }

        // update participant right away
        civicrm_api3(
        'Participant',
        'create',
        [
          'id' => $participant_id,
          'status_id' => $new_status,
        ]
        );
        // mark as updated to avoid additional calls
        // see also: https://github.com/systopia/de.systopia.remoteevent/issues/19
        $registration->setParticipantUpdated();
      }
      else {
        // there is no pre-existing participant, just add to the general to-be-created one
        if (empty($submission['confirm'])) {
          $participant = &$registration->getParticipantData();
          $participant['status_id'] = 'Cancelled';
        }
      }
    }

}
