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
use \Civi\RemoteEvent\Event\GetParamsEvent as GetParamsEvent;

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
        $hours_before_allowed = (int) $event['selfcancelxfer_time'];
        $hours_before = (strtotime($event['event_start_date']) - strtotime('now')) / 60 / 60;
        if ($hours_before < $hours_before_allowed) {
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
        $hours_before_allowed = (int) $event['selfcancelxfer_time'];
        $hours_before = (strtotime($event['event_start_date']) - strtotime('now')) / 60 / 60;
        if ($hours_before < $hours_before_allowed) {
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
            $registered_count = CRM_Remoteevent_Registration::getRegistrationCount($event_id);
            return ($registered_count >= $event_data['max_participants']);
        } else {
            return false;
        }
    }
}
