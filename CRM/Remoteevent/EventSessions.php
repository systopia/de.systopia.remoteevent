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
use \Civi\RemoteParticipant\Event\GetCreateParticipantFormEvent;
use \Civi\RemoteParticipant\Event\ValidateEvent;
use \Civi\RemoteParticipant\Event\ChangingEvent;
use \Civi\EventMessages\MessageTokens;

/**
 * RemoteEvent logic for sessions
 */
class CRM_Remoteevent_EventSessions
{
    /** @var null|array list of sessions submitted with the current proces */
    protected static $sessions_in_progress = null;

    /**
     * Add the profile data to the get_form results
     *
     * @param GetCreateParticipantFormEvent $get_form_results
     *      event triggered by the RemoteParticipant.get_form API call
     */
    public static function addSessionFields($get_form_results)
    {
        $l10n = $get_form_results->getLocalisation();
        $full_prefix = $l10n->localise("[FULL] ");

        $event = $get_form_results->getEvent();
        $session_data = CRM_Remoteevent_BAO_Session::getSessions($event['id'], true, $event['start_date']);
        $participant_counts = CRM_Remoteevent_BAO_Session::getParticipantCounts($event['id']);

        if (empty($session_data)) {
            // no sessions
            return;
        }

        // clean up: remove inactive session, add 'full' info
        foreach (array_keys($session_data) as $session_key) {
            $_session = &$session_data[$session_key];
            if (empty($_session['is_active'])) {
                unset($session_data[$session_key]);
            } else {
                $_session['participant_count'] = CRM_Utils_Array::value($_session['id'], $participant_counts, 0);
                if (empty($_session['max_participants'])) {
                    $_session['is_full'] = 0;
                } else {
                    $_session['is_full'] = ($_session['participant_count'] >= $_session['max_participants']) ? 1 : 0;
                }
            }
        }

        // sort sessions by day and slot
        $sessions_by_day_and_slot = [];
        foreach ($session_data as $session) {
            $session_day = $session['day'];
            $session_slot = empty($session['slot_id']) ? '' : $session['slot_id'];
            $sessions_by_day_and_slot[$session_day][$session_slot][] = $session;
        }

        // start listing fields
        $weight = 200;
        $get_form_results->addFields(
            [
                'sessions' => [
                    'type' => 'fieldset',
                    'name' => 'sessions',
                    'label' => E::ts("Workshops"),
                    'weight' => $weight,
                    'description' => '',
                ]
            ]
        );
        foreach ($sessions_by_day_and_slot as $day => $slot_sessions) {
            $weight = $weight + 1;
            foreach ($slot_sessions as $slot_id => $sessions) {
                if (empty($sessions)) {
                    continue;
                }

                if ($slot_id) {
                    // add name slot fieldset
                    $slot_name = CRM_Remoteevent_BAO_Session::getSlotLabel($slot_id);
                    $group_name = "day{$day}slot{$slot_id}_group";
                    $group_label = count($sessions_by_day_and_slot) > 1 ? // i.e. multi-day event
                        E::ts("Workshops - Day %1 - %2", [1 => $day, 2 => $slot_name]) :
                        E::ts("Workshops - %1", [1 => $slot_name]);

                    // add group
                    $get_form_results->addFields(
                        [
                            $group_name => [
                                'type' => 'fieldset',
                                'name' => $group_name,
                                'label' => $group_label,
                                'weight' => $weight,
                                'parent' => 'sessions',
                                'description' => '',
                            ]
                        ]
                    );
                } else {
                    // add open (no slot) fieldset
                    $group_name = "day{$day}_group";
                    $group_label = count($sessions_by_day_and_slot) > 1 ? // i.e. multi-day event
                        E::ts("Workshops - Day %1", [1 => $day]) :
                        E::ts("Workshops");

                    // add group
                    $get_form_results->addFields(
                        [
                            $group_name => [
                                'type' => 'fieldset',
                                'name' => $group_name,
                                'label' => $group_label,
                                'weight' => $weight,
                                'parent' => 'sessions',
                                'description' => '',
                            ]
                        ]
                    );
                }


                foreach ($sessions as $session) {
                    // enrich the session data
                    $weight += 1;
                    $session['type'] = CRM_Remoteevent_BAO_Session::getSessionTypeLabel($session['type_id']);
                    $session['category'] = CRM_Remoteevent_BAO_Session::getSessionCategoryLabel(
                        $session['category_id']
                    );

                    if ($slot_id) {
                        // if this is a (real) slot
                        //   the session participation is mutually exclusive for the sessions in the slot

                        $get_form_results->addFields(
                            [
                                "session{$session['id']}" => [
                                    'name' => "day{$day}slot{$slot_id}",
                                    'type' => 'Radio',
                                    'weight' => $weight,
                                    'label' => self::renderSessionLabel($session, $full_prefix),
                                    'description' => self::renderSessionDescriptionShort($session),
                                    'parent' => "day{$day}slot{$slot_id}_group",
                                    // don't use this here, causes trouble: 'disabled' => empty($session['is_full']) ? 0 : 1,
                                    'suffix' => self::renderSessionDescriptionLong($session),
                                    'suffix_display' => 'dialog',
                                    'suffix_dialog_label' => E::ts("Details"),
                                    'required' => 0,
                                ]
                            ]
                        );
                    } else {
                        // no slot assigned
                        $get_form_results->addFields(
                            [
                                "session{$session['id']}" => [
                                    'name' => "session{$session['id']}",
                                    'type' => 'Checkbox',
                                    'weight' => $weight,
                                    'label' => self::renderSessionLabel($session, $full_prefix),
                                    'description' => self::renderSessionDescriptionShort($session),
                                    'parent' => "day{$day}_group",
                                    // don't use this here, causes trouble: 'disabled' => empty($session['is_full']) ? 0 : 1,
                                    'suffix' => self::renderSessionDescriptionLong($session),
                                    'suffix_display' => 'dialog',
                                    'suffix_dialog_label' => E::ts("Details"),
                                    'required' => 0,
                                ]
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Add the the sessions the participant's registered for
     *   as a default selections
     *
     * @param GetCreateParticipantFormEvent $get_form_results
     *      event triggered by the RemoteParticipant.get_form API call
     */
    public static function addRegisteredSessions($get_form_results)
    {
        $participant_id = $get_form_results->getParticipantID();
        if ($participant_id) {
            $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($participant_id);
            foreach ($registered_session_ids as $registered_session_id) {
                $get_form_results->setPrefillValue("session{$registered_session_id}", '1', false);
            }
        }
    }


    /**
     * Validate the session registrations:
     *  1) make sure that none of them are booked out (except if the participant is already signed up)
     *  2) make sure they are not at the some time or in the same slot
     *
     * @param ValidateEvent $validationEvent
     *      event triggered by the RemoteParticipant.validate or submit API call
     */
    public static function validateSessionSubmission($validationEvent)
    {
        $event_id = $validationEvent->getEventID();
        if (!$event_id) {
            // this really shouldn't happen
            $validationEvent->addError('Event ID not found.');
            return;
        }

        // check if this even concerns us:
        $requested_session_ids = self::getSubmittedSessionIDs($validationEvent->getQueryParameters());
        if (empty($requested_session_ids)) {
            return;
        }

        // load the event's sessions
        $sessions = CRM_Remoteevent_BAO_Session::getSessions($event_id);
        $participant_counts = null;

        // load the current
        $registered_session_ids = [];
        $participant_id = $validationEvent->getParticipantID();
        if ($participant_id) {
            $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($participant_id);
        }

        // CHECK IF SPACE AVAILABLE IN REQUESTED SESSIONS
        foreach ($requested_session_ids as $requested_session_id) {
            if (in_array($requested_session_id, $registered_session_ids)) {
                continue; // we don't need to check, if contact already registered there
            }

            $session = $sessions[$requested_session_id];
            if (!empty($session['max_participants'])) {
                // here we need to check
                if ($participant_counts === null) { // lazy load
                    $participant_counts = CRM_Remoteevent_BAO_Session::getParticipantCounts($event_id);
                }

                $session_participant_count = CRM_Utils_Array::value($requested_session_id, $participant_counts, 0);
                if ($session_participant_count >= $session['max_participants']) {
                    $validationEvent->addValidationError("session{$requested_session_id}", E::ts("Session is full"));
                }
            }
        }

        // load sessions
        $sessions = civicrm_api3('Session', 'get', [
            'id'           => ['IN' => $requested_session_ids],
            'return'       => 'id,start_date,end_date,slot_id',
            'option.limit' => 0,
            'option.sort'  => 'start_date asc',
            'sequential'   => 1,
        ])['values'];

        // CHECK IF THERE IS A TIME COLLISION
        $last_time = null;
        foreach ($sessions as $session) {
            if ($last_time && strtotime($session['start_date']) < $last_time) {
                $validationEvent->addValidationError("session{$session['id']}", E::ts("You can't register for two sessions with overlapping time"));
            }
            $last_time = strtotime($session['end_date']);
        }

        // CHECK IF THERE IS A SLOT COLLISION
        $occupied_slots = [];
        foreach ($sessions as $session) {
            if (!empty($session['slot_id'])) {
                $slot_id = $session['slot_id'];
                if (in_array($slot_id, $occupied_slots)) {
                    $validationEvent->addValidationError("session{$session['id']}", E::ts("You can't register for two sessions in the same slot"));
                } else {
                    $occupied_slots[] = $slot_id;
                }
            }
        }
    }

    /**
     * @param ChangingEvent $event
     */
    public static function synchroniseSessions(ChangingEvent $event)
    {
        // only do something when there's no error
        if ($event->hasErrors()) {
            return;
        }

        // only do something when there's a participant
        $participant_id = $event->getParticipantID();
        if (!$participant_id) {
            return;
        }

        // get the old and the new registrations
        $requested_session_ids = self::getSubmittedSessionIDs($event->getQueryParameters());

        // todo: what to do if the sessions aren't submitted?
        CRM_Remoteevent_BAO_Session::setParticipantRegistrations($participant_id, $requested_session_ids);

        // now that they're written, we can reset this
        self::$sessions_in_progress = null;
    }


    /**
     * Extract the session IDs from the submission,
     *   as generated by the 'addSessionFields' function above
     *
     * @param array $submission
     */
    public static function getSubmittedSessionIDs($submission)
    {
        $session_ids = [];
        foreach ($submission as $key => $value) {
            if (!empty($value) && preg_match('/^session[0-9]+$/', $key)) {
                $session_ids[] = (int) substr($key, 7);
            }
        }
        return $session_ids;
    }

    /**
     * Extract the submitted sessions from the submitted data
     *   and store in a static variable.
     * The reason for this handover is, that the token events below might be triggered
     *   before the participants have been written to the DB
     *
     * @param ChangingEvent $event
     *   token list event
     */
    public static function extractSessions($event)
    {
        $submission = $event->getSubmission();
        self::$sessions_in_progress = self::getSubmittedSessionIDs($submission);
    }

    /**
     * Define/list the additional session tokens
     *
     * @param MessageTokenList $tokenList
     *   token list event
     */
    public static function listTokens($tokenList)
    {
        $tokenList->addToken('$participant_sessions', E::ts("List of sessions the participant is registered to (as array)"));
        $tokenList->addToken('$participant_sessions_list_html', E::ts("List of sessions the participant is registered to (as html list)."));
        $tokenList->addToken('$participant_sessions_list_txt', E::ts("List of sessions the participant is registered to (as ascii list)."));
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
            // cached sessions (for this event)
            $all_event_sessions = CRM_Remoteevent_BAO_Session::getSessions($tokens['participant']['event_id']);

            // get sessions for this participant
            if (self::$sessions_in_progress === null)  {
                $participant_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($tokens['participant']['id']);
            } else {
                $participant_session_ids = self::$sessions_in_progress;
            }

            $participant_sessions = [];
            foreach ($all_event_sessions as $session) {  // use this inverse lookup to maintain the order
                if (in_array($session['id'], $participant_session_ids)) {
                    $session['category']      = self::getSessionCategory($session);
                    $session['type']          = self::getSessionType($session);
                    $session['presenter_txt'] = self::getSessionPresenterText($session);
                    $session['location_txt']  = self::getSessionLocationText($session, false);
                    $session['location_html'] = self::getSessionLocationText($session, true);
                    $participant_sessions[] = $session;
                }
            }
            $messageTokens->setToken('participant_sessions', $participant_sessions, false);

            // generate a HTML bullet point list token
            if (empty($participant_sessions)) {
                $messageTokens->setToken('participant_sessions_list_html', '');
            } else {
                $messageTokens->setToken(
                    'participant_sessions_list_html',
                    \Civi\RenderEvent::renderTemplate(
                        E::path('resources/remote_token_sessions_list_html.tpl'),
                        ['sessions' => $participant_sessions],
                        'remoteevent.eventmessages.participant.sessions.htmllist',
                        'none'
                    )
                );
            }

            // generate a ASCII bullet point list token
            if (empty($participant_sessions)) {
                $messageTokens->setToken('participant_sessions_list_txt', '');
            } else {
                $messageTokens->setToken(
                    'participant_sessions_list_txt',
                    \Civi\RenderEvent::renderTemplate(
                        E::path('resources/remote_token_sessions_list_txt.tpl'),
                        ['sessions' => $participant_sessions],
                        'remoteevent.eventmessages.participant.sessions.asciilist',
                        'none'
                    )
                );
            }
        }
    }


    /**
     * Get a rendered location string for the session
     *
     * @param array $session_data
     *    session data
     *
     * @param boolean $html
     *    do we want HTML data?
     *
     * @return string
     *    session location or empty string
     */
    protected static function getSessionLocationText($session_data, $html)
    {
        if (empty($session_data['location'])) {
            return '';
        } else {
            if ($html) {
                return $session_data['location'];
            } else {
                //return CRM_Utils_String::htmlToText($session_data['location']);
                return strip_tags($session_data['location']);
            }
        }
    }

    /**
     * Get a rendered presenter string for the session
     *
     * @param array $session_data
     *    session data
     *
     * @return string
     *    session presenter or empty string
     */
    protected static function getSessionPresenterText($session_data)
    {
        static $presenter_names = [];
        if (empty($session_data['presenter_id'])) {
            return ''; // no presenter given
        } else {
            $presenter_id = (int)$session_data['presenter_id'];
            if (!isset($presenter_names[$presenter_id])) {
                $presenter_names[$presenter_id] = civicrm_api3(
                    'Contact',
                    'getvalue',
                    [
                        'id' => $presenter_id,
                        'return' => 'display_name'
                    ]
                );
            }
            if (empty($session_data['presenter_title'])) {
                return E::ts("Given by %1", [1 => $presenter_names[$presenter_id]]);
            } else {
                return E::ts("%1 is %2", [1 => $session_data['presenter_title'], 2 => $presenter_names[$presenter_id]]);
            }
        }
    }


    /**
     * Get the category label for the given session data
     *
     * @param array $session_data
     *    session data
     *
     * @return string
     *    session category
     */
    public static function getSessionType($session_data)
    {
        static $types = null;
        if ($types === null) {
            $types = [];
            $query = civicrm_api3('OptionValue', 'get', [
                'option_group_id' => 'session_type',
                'option.limit'    => 0,
                'return'          => 'label,value'
            ]);
            foreach ($query['values'] as $type) {
                $types[$type['value']] = $type['label'];
            }
        }

        if (empty($session_data['type_id'])) {
            return E::ts("no type"); // this shouldn't happen
        } else {
            return CRM_Utils_Array::value($session_data['type_id'], $types, E::ts("unknown"));
        }
    }

    /**
     * Get the category label for the given session data
     *
     * @param array $session_data
     *    session data
     *
     * @return string
     *    session category
     */
    public static function getSessionCategory($session_data)
    {
        static $categories = null;
        if ($categories === null) {
            $categories = [];
            $query = civicrm_api3('OptionValue', 'get', [
                'option_group_id' => 'session_category',
                'option.limit'    => 0,
                'return'          => 'label,value'
            ]);
            foreach ($query['values'] as $category) {
                $categories[$category['value']] = $category['label'];
            }
        }

        if (empty($session_data['category_id'])) {
            return E::ts("no category"); // this shouldn't happen
        } else {
            return CRM_Utils_Array::value($session_data['category_id'], $categories, E::ts("unknown"));
        }
    }



    /**
     * Render the label for the given session data
     *  in the registration form
     *
     * @param array $session
     *   the session data as produced by the API
     *
     * @return string
     *   session label
     */
    protected static function renderSessionLabel($session, $full_text)
    {
        $start_time = date('H:i', strtotime($session['start_date']));
        $end_time = date('H:i', strtotime($session['end_date']));
        $full_marker = empty($session['is_full']) ? '' : $full_text;
        return "{$full_marker}[{$start_time}-{$end_time}] {$session['title']}";
    }

    /**
     * Render the a short description for the given session data
     *  in the registration form
     *
     * @param array $session
     *   the session data as produced by the API
     *
     * @return string
     *   session label
     */
    protected static function renderSessionDescriptionShort($session)
    {
        return \Civi\RenderEvent::renderTemplate(
            E::path('resources/remote_session_short_description.tpl'),
            ['session' => $session],
            'remoteevent.session.description.short',
            'trim'
        );
    }

    /**
     * Render the label for the given session data
     *  in the registration form
     *
     * @param array $session
     *   the session data as produced by the API
     *
     * @return string
     *   session label
     */
    protected static function renderSessionDescriptionLong($session)
    {
        return \Civi\RenderEvent::renderTemplate(
            E::path('resources/remote_session_description.tpl'),
            ['session' => $session],
            'remoteevent.session.description.long',
            'trim'
        );
    }

}
