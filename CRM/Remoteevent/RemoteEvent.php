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
use \Civi\EventMessages\MessageTokens as MessageTokens;
use \Civi\EventMessages\MessageTokenList as MessageTokenList;
use Civi\RemoteEvent;
use \Civi\RemoteEvent\Event\GetParamsEvent as GetParamsEvent;
use Civi\Api4\Participant;
use Civi\Api4\ParticipantStatusType;

/**
 * Basic function regarding remote events
 */
class CRM_Remoteevent_RemoteEvent
{
    const STRIP_FIELDS = [
        'is_online_registration',
        'event_full_text',
        'is_map',
        'is_show_location',
        'created_id',
        'created_date'
    ];


    /** @var array cached events, indexed by event_id */
    protected static $event_cache = [];

    /**
     * Get the given event data.
     *
     *  Warning: uses RemoteEvent.get, don't cause recursions!
     *
     * @param integer $event_id
     *   event ID
     *
     * @return array
     *   event data
     */
    public static function getRemoteEvent($event_id)
    {
        $event_id = (int) $event_id;
        if (!$event_id) {
            return null;
        }

        if (!isset(self::$event_cache[$event_id])) {
            self::$event_cache[$event_id] = civicrm_api3('RemoteEvent', 'getsingle', [
                'id' => $event_id
            ]);
        }
        return self::$event_cache[$event_id];
    }

    /**
     * Invalidate some event, e.g. drop from the cache
     *
     * @param integer $event_id
     *   event ID to drop
     */
    public static function invalidateRemoteEvent($event_id)
    {
        unset(self::$event_cache[$event_id]);
        CRM_Remoteevent_EventCache::invalidateEvent($event_id);
    }

    /**
     * Add some tokens to an event message:
     *  - cancellation token
     *
     * @param MessageTokens $messageTokens
     *   the token list
     */
    public static function addTokens(MessageTokens $messageTokens)
    {
        $tokens = $messageTokens->getTokens();
        if (!empty($tokens['participant'])) {
            $participant = $tokens['participant'];
            if (self::shouldAddCancellationLink($participant)) {
                $cancellation_token = CRM_Remotetools_SecureToken::generateEntityToken(
                    'Participant',
                    $participant['id'],
                    null,
                    'cancel'
                );
                $messageTokens->setToken('cancellation_token', $cancellation_token);

                // add URL
                $url = Civi::settings()->get('remote_registration_cancel_link');
                if ($url) {
                    $link = preg_replace('/\{token\}/', $cancellation_token, $url);
                    $messageTokens->setToken('cancellation_link', $link);
                }
            }
            if (self::shouldAddUpdateLink($participant)) {
                $update_token = CRM_Remotetools_SecureToken::generateEntityToken(
                    'Participant',
                    $participant['id'],
                    null,
                    'update'
                );
                $messageTokens->setToken('update_token', $update_token);

                // add URL
                $url = Civi::settings()->get('remote_registration_modify_link');
                if ($url) {
                    $link = preg_replace('/\{token\}/', $update_token, $url);
                    $messageTokens->setToken('update_link', $link);
                }
            }
        }
    }

    /**
     * @param MessageTokenList $tokenList
     *   token list event
     */
    public static function listTokens($tokenList)
    {
        $tokenList->addToken('$cancellation_token', E::ts("Cancellation Token valid for one-click cancellation, if available."));
        $tokenList->addToken('$cancellation_link', E::ts("URL for one-click cancellation, if available. This also requires the base url to set in the RemoteEvent general settings."));
        $tokenList->addToken('$update_token', E::ts("Token to edit/update participant's registration."));
        $tokenList->addToken('$update_link', E::ts("Link to a form to edit/update participant's registration. This also requires the base url to set in the RemoteEvent general settings."));
    }


    /**
     * Should a cancellation link be generated for the given participant?
     *
     * @param array $participant
     *   participant data
     *
     * @return bool
     */
    public static function shouldAddCancellationLink($participant)
    {
        // check if status is not (already) negative
        $all_statuses = CRM_Remoteevent_Registration::getParticipantStatusList();
        $participant_status = \CRM_Utils_Array::value($participant['participant_status_id'], $all_statuses);
        if (empty($participant_status)) {
            return false;
        } else {
            if ($participant_status['class'] == 'Negative') {
                return false;
            }
        }

        // check if cancellation is active
        $event = CRM_Remoteevent_EventCache::getEvent($participant['event_id']);
        if (empty($event['allow_selfcancelxfer'])) {
            return false;
        }

        // check if there's still time
        if (!CRM_Remoteevent_Registration::cancellationStillAllowed($event)) {
            return false;
        }

        return true;
    }

    /**
     * Should a update/modify link be generated for the given participant?
     *
     * @param array $participant
     *   participant data
     *
     * @return bool
     */
    public static function shouldAddUpdateLink($participant)
    {
        // check if status is not (already) negative
        $all_statuses = CRM_Remoteevent_Registration::getParticipantStatusList();
        $participant_status = \CRM_Utils_Array::value($participant['participant_status_id'], $all_statuses);
        if (empty($participant_status)) {
            return false;
        } else {
            if ($participant_status['class'] == 'Negative') {
                return false;
            }
        }

        // check if cancellation is active
        $event = CRM_Remoteevent_EventCache::getEvent($participant['event_id']);
        if (empty($event['allow_selfcancelxfer'])) {
            return false;
        }

        // check if there's still time
        if (!CRM_Remoteevent_Registration::cancellationStillAllowed($event)) {
            return false;
        }

        return true;
    }

    /**
     * If there is token, we can set the event_id
     *
     * @param GetParamsEvent $get_parameters
     *
     */
    public static function deriveEventID($get_parameters)
    {
        $get_event_params = $get_parameters->getOriginalParameters();
        if (empty($get_event_params['event_id'])) {
            $event_id = $get_parameters->getEventID();
            if ($event_id) {
                $get_parameters->setParameter('event_id', $event_id);
            }
        }
    }

    /**
     * Does the given event have an active waitlist,
     *  i.e. a waitlist is enabled and the event is full
     *
     * @param integer $event_id
     *   ID of the event
     *
     * @param array $event_data
     *   if passed, this event data is used, otherwise the data will be loaded
     *
     * @return bool
     *   true if the event does have an active waiting list
     */
    public static function hasActiveWaitingList($event_id, $event_data = null)
    {
        if (empty($event_data)) {
            $event_data = self::getRemoteEvent($event_id);
        }

        if (!empty($event_data['has_waitlist']) && !empty($event_data['max_participants'])) {
            // there is an active waiting list, see if we need to get on it
            $registered_count = CRM_Remoteevent_Registration::getRegistrationCount(
                $event_id,
                NULL,
                ['Positive', 'Pending']
            );
            return ($registered_count >= $event_data['max_participants']);
        } else {
            return false;
        }
    }

    /**
     * Get the type of the event
     *
     * @param array $event_data
     *   event data, looking for entry 'event_type_id'
     *
     * @param CRM_Remoteevent_Localisation $locale
     *   pass a localisation if you want the translated values
     *
     * @return string name of the event type
     */
    public static function getEventType($event, $locale = null)
    {
        $event_type_id = CRM_Utils_Array::value('event_type_id', $event, '');

        if ($event_type_id) {
            // load event types
            static $event_types = null;
            if ($event_types === null) {
                $event_types = [];
                $event_type_query = civicrm_api3('OptionValue', 'get', [
                    'option_group_id' => 'event_type',
                    'return'          => 'value,label',
                    'option.limit'    => 0
                ]);
                foreach ($event_type_query['values'] as $value) {
                    $event_types[$value['value']] = $value['label'];
                }
            }

            // look up the type
            if (isset($event_types[$event_type_id])) {
                if ($locale) {
                    return $locale->ts($event_types[$event_type_id]);
                } else {
                    return $event_types[$event_type_id];
                }
            } else {
                if ($locale) {
                    return $locale->ts("Unknown");
                } else {
                    return E::ts("Unknown");
                }
            }
        } else {
            return ''; // has no event type
        }
    }

    /**
     * Check if the given event is a template
     *
     * @param $event_id integer
     *   event id
     *
     * @return integer 0|1
     */
    public static function isTemplate($event_id)
    {
        // todo: caching?
        $event_id = (int) $event_id;
        try {
            return (int) civicrm_api3('Event', 'getvalue', ['return' => 'is_template', 'id' => $event_id]);
        } catch (CiviCRM_API3_Exception $ex) {
            // event probably doesn't exist
            return 0;
        }
    }

    /**
     * Retrieves information about additional participants registered by the
     * given participant. The format is the result of a Participant.autocomplete
     * APIv4 call.
     *
     * @param int $participantId
     *   The ID of the participant to retrieve additionally registered
     *   participants for
     *
     * @return array
     */
    public static function getAdditionalParticipantInfo(int $participantId, RemoteEvent $event): array
    {
        $participant = Participant::get(FALSE)
            ->addSelect('event_id', 'contact_id')
            ->addWhere('id', '=', $participantId)
            ->execute()
            ->single();
        $participants = CRM_Remoteevent_Registration::getRegistrations($participant['event_id'], $participant['contact_id']);
        $nonNegativeParticipantStatusIds = ParticipantStatusType::get(FALSE)
          ->addSelect('id')
          ->addWhere('class:name', '!=', 'Negative')
          ->execute()
          ->column('id');
        $additional_participant_ids = Participant::get(FALSE)
            ->addWhere(
                'registered_by_id',
                'IN',
                array_column($participants, 'id')
            )
            ->addWhere('status_id', 'IN', $nonNegativeParticipantStatusIds)
            ->execute()
            ->column('id');

        if (!empty($additional_participant_ids)) {
            $additional_participants = Participant::autocomplete(FALSE)
                ->setIds($additional_participant_ids)
                ->execute()
                ->getArrayCopy();
            array_walk($additional_participants, function(&$participant) use ($event) {
                $participant['message'] = implode(
                    ' ',
                    [
                        $participant['description'][0] ?? '#' . $participant['id'],
                        $participant['label'],
                        '[' . ($participant['description'][1] ?? $event->localise('Unknown status')) . ']'
                    ]
                );
            });
        }

      return $additional_participants ?? [];
    }
}
