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

/**
 * Basic function regarding remote events
 */
class CRM_Remoteevent_RemoteEvent
{
    const API_SEPARATOR = '__';

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
                    $messageTokens->setToken('cancellation_link', "{$url}?token={$cancellation_token}");
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

}
