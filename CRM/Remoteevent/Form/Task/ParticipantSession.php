<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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
use \Civi\RemoteParticipant\Event\ValidateEvent;


/**
 * Send E-Mail to participants task
 */
class CRM_Remoteevent_Form_Task_ParticipantSession extends CRM_Event_Form_Task
{
    /** @var integer the ID of the event of all participants */
    protected $event_id = null;

    /** @var string list of all participant IDs involved */
    protected $participant_id_list;

    /** @var array list of sessions */
    protected $sessions = null;

    /**
     * Compile task form
     */
    function buildQuickForm()
    {
        // first: check if this is all the same event
        $this->participant_id_list = implode(',', $this->_participantIds);
        $this->event_id = $this->getEventID();
        if (empty($this->event_id)) {
            // if this is multiple events, we bail - we can only work with one
            CRM_Core_Session::setStatus(
                E::ts("The selected participants belong to more than one event. This task only works on participants of the same event. Sorry"),
                E::ts("Multiple Events Selected"),
                'warn'
            );
            $user_context = CRM_Core_Session::singleton()->popUserContext();
            CRM_Utils_System::redirect($user_context);
        }

        // set title
        $event_title = civicrm_api3('Event', 'getvalue', ['id' => $this->event_id, 'return' => 'title']);
        CRM_Utils_System::setTitle(E::ts("Register/unregister sessions of event '%1'", [1 => $event_title]));

        // load sessions
        $this->sessions = CRM_Remoteevent_BAO_Session::getSessions($this->event_id);
        $participant_counts = CRM_Remoteevent_BAO_Session::getParticipantCounts($this->event_id);
        $slots = CRM_Remoteevent_Form_EventSessions::getSlots();
        $session_list = [];
        foreach ($this->sessions as $session) {
            // compile label
            $session_title = $session['title'];

            // add slot
            if (empty($session['slot_id'])) {
                $session_title .= " [{$slots[$session['slot_id']]}]";
            //} else {
            //    $session_title .= " [{$slots['no_slot']}]";
            }

            // add participant counts if restricted
            if (!empty($session['max_participants'])) {
                $session_participant_count = CRM_Utils_Array::value($session['id'], $participant_counts, 0);
                $free = $session['max_participants'] - $session_participant_count;
                if ($free > 0) {
                    $session_title .= ' ' . E::ts('(%1 left)', [1 => $free]);
                } else {
                    $session_title .= ' ' . E::ts('(full)');
                }
            }
            if (empty($session['is_active'])) {
                $session_title .= ' ' . E::ts('(deactivated)');
            }
            $session_list[$session['id']] = $session_title;
        }

        // build form
        $this->add(
            'checkbox',
            'bypass_restriction',
            E::ts("Bypass restrictions")
        );

        $this->add(
            'select',
            'unregister',
            E::ts('Mode'),
            [
                '0' => E::ts("Register (%1 participants)", [1 => count($this->_participantIds)]),
                '1' => E::ts("Unregister (%1 participants)", [1 => count($this->_participantIds)]),
            ],
            false,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'session_ids',
            E::ts('Sessions'),
            $session_list,
            true,
            ['class' => 'crm-select2 huge', 'multiple' => 'multiple']
        );

        CRM_Core_Form::addDefaultButtons(E::ts("Apply"));
    }


    /**
     * Will return the unique event ID for the participants involved
     * If the participants have different event IDs, null is returned
     *
     * @return null|integer
     *   the ID of the event involved or null if not unique
     */
    protected function getEventID()
    {
        if (!empty($this->_participantIds)) {
            $event_ids_query = CRM_Core_DAO::executeQuery("
                SELECT DISTINCT(event_id) AS event_id
                FROM civicrm_participant
                WHERE id IN ({$this->participant_id_list})
                LIMIT 2");

            // the first one is the event_id
            $event_ids_query->fetch();
            $event_id = $event_ids_query->event_id;

            // if there is a second one, that's bad:
            if ($event_ids_query->fetch()) {
                return null;
            } else {
                return $event_id;
            }
        }

        // no event
        return null;
    }

    /**
     * Apply the selection
     */
    function postProcess()
    {
        $values = $this->exportValues();
        $session_id_list = implode(',', $values['session_ids']);
        if (!empty($values['unregister'])) {

            // UNSUBSCRIBE MODE

            // FIRST: get the count so we can report that:
            $unregister_count = CRM_Core_DAO::singleValueQuery("
                SELECT COUNT(*) 
                FROM civicrm_participant_session
                WHERE session_id IN ({$session_id_list})
                  AND participant_id IN ({$this->participant_id_list})
            ");

            // THEN: delete those, no restrictions
            CRM_Core_DAO::executeQuery("
                DELETE FROM civicrm_participant_session
                WHERE session_id IN ({$session_id_list})
                  AND participant_id IN ({$this->participant_id_list})
            ");

            CRM_Core_Session::setStatus(
                E::ts("%1 existing session participations cancelled", [1 => $unregister_count]),
                E::ts("Participants unregistered"),
                'info'
            );

        } else {

            // SUBSCRIBE MODE

            if (!empty($values['bypass_restriction'])) {

                // BYPASS RESTRICTIONS => just insert

                $count_before = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_participant_session");

                CRM_Core_DAO::executeQuery("
                    INSERT INTO civicrm_participant_session (session_id,participant_id)
                    SELECT
                        session.id     AS session_id,
                        participant.id AS participant_id
                    FROM civicrm_participant participant
                    LEFT JOIN civicrm_session session
                           ON session.event_id = participant.event_id
                           AND session.id IN ({$session_id_list})    
                    LEFT JOIN civicrm_participant_session participant_session
                           ON participant_session.participant_id = participant.id
                           AND participant_session.session_id = session.id
                    WHERE participant.id IN ({$this->participant_id_list})
                      AND participant_session.id IS NULL");

                $count_after = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_participant_session");

                CRM_Core_Session::setStatus(
                    E::ts("%1 new session participations recorded", [1 => ($count_after - $count_before)]),
                    E::ts("Participants registered"),
                    'info'
                );

            } else {

                // FIRST: get list of requested subscriptions
                $subscription_jobs = CRM_Core_DAO::executeQuery("
                    SELECT
                        GROUP_CONCAT(CONCAT('session', session.id) SEPARATOR ',') AS session_list,
                        participant.id                                            AS participant_id
                    FROM civicrm_participant participant
                    LEFT JOIN civicrm_session session
                           ON session.event_id = participant.event_id
                           AND session.id IN ({$session_id_list})    
                    LEFT JOIN civicrm_participant_session participant_session
                           ON participant_session.participant_id = participant.id
                           AND participant_session.session_id = session.id
                    WHERE participant.id IN ({$this->participant_id_list})
                      AND participant_session.id IS NULL
                    GROUP BY participant.id
                ");

                // CHECK RESTRICTIONS
                $subscription_counter = 0;
                $subscription_failed_counter = 0;
                while ($subscription_jobs->fetch()) {
                    // run check using we'll just use the internal validation function:
                    $session_list = explode(',', $subscription_jobs->session_list);
                    $session_submission = [];
                    foreach ($session_list as $session_key) {
                        $session_submission[$session_key] = 1;
                    }
                    $submission_event = new ValidateEvent($session_submission);
                    $submission_event->overrideParticipant($subscription_jobs->participant_id);
                    CRM_Remoteevent_EventSessions::validateSessionSubmission($submission_event);

                    // remove the ones that have triggered an error
                    $errors = $submission_event->getErrors();
                    foreach ($errors as $error) {
                        $error_field = $error[1];
                        if (($key = array_search($error_field, $session_list)) !== false) {
                            unset($session_list[$key]);
                            $subscription_failed_counter += 1;
                        }
                    }

                    // subscribe the rest
                    foreach ($session_list as $session_key) {
                        if (preg_match('/^session[0-9]+$/', $session_key)) {
                            $session_id = (int) substr($session_key, 7);
                            CRM_Core_DAO::executeQuery("
                                INSERT INTO civicrm_participant_session (participant_id,session_id)
                                VALUES ({$subscription_jobs->participant_id},{$session_id});");
                            $subscription_counter += 1;
                        }
                    }
                }

                // add status
                CRM_Core_Session::setStatus(
                    E::ts("%1 new session participations recorded, %2 participations rejected", [
                        1 => $subscription_counter, 2 => $subscription_failed_counter]),
                    E::ts("Participants registered"),
                    'info'
                );
            }
        }
    }
}

