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
use Civi\RemoteParticipant\Event\ValidateEvent as ValidateEvent;
/**
 * Form to edit the sessions for a specific participant
 */
class CRM_Remoteevent_Form_ParticipantSessions extends CRM_Core_Form
{
    /** @var integer participant id */
    protected $participant_id = null;

    public function buildQuickForm()
    {
        // load participant, sessions, registrations
        $this->participant_id = CRM_Utils_Request::retrieve('participant_id', 'Integer', $this, true);
        $this->participant = civicrm_api3('Participant', 'getsingle', ['id' => $this->participant_id]);
        $this->sessions = CRM_Remoteevent_BAO_Session::getSessions($this->participant['event_id']);
        $this->registrations = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($this->participant_id);

        // set title
        $this->setTitle(E::ts("Session Assignment for '%1' in event '%2'", [
            1 => $this->participant['display_name'],
            2 => $this->participant['event_title']
        ]));

        // build form
        $this->add(
            'checkbox',
            'bypass_restriction',
            E::ts("Bypass restrictions")
        );

        $session_fields = [];
        foreach ($this->sessions as $session) {
            $session_fields[] = "session{$session['id']}";
            $this->add(
                'checkbox',
                "session{$session['id']}",
                $session['title']
            );
        }
        $this->assign('session_fields', $session_fields);

        // set current values
        foreach ($this->registrations as $session_id) {
            $this->setDefaults(["session{$session_id}" => 1]);
        }

        $this->addButtons(
            [
                [
                    'type' => 'submit',
                    'name' => E::ts('Update Registration'),
                    'isDefault' => true,
                ],
            ]
        );

        parent::buildQuickForm();
    }

    /**
     * enhanced validation for the submissons,
     *  testing e.g. max participants and time overlaps
     *
     * will not be executed, if bypass_restriction is set
     */
    public function validate()
    {
        parent::validate();
        if (empty($this->_submitValues['bypass_restriction'])) {

            // we'll just use the internal validation function:
            // todo: trigger the full validation event? then we'd have to add more overrides...
            $submission_event = new ValidateEvent($this->_submitValues);
            $submission_event->overrideParticipant($this->participant_id);
            CRM_Remoteevent_EventSessions::validateSessionSubmission($submission_event);

            // apply errors to the fields
            $errors = $submission_event->getErrors();
            foreach ($errors as $error) {
                $this->_errors[$error[1]] = $error[0];
            }
        }

        return (0 == count($this->_errors));
    }


    public function postProcess()
    {
        $values = $this->exportValues();

        // update selectes sessions
        $selected_sessions = [];
        foreach ($values as $key => $value) {
            if (!empty($value) && preg_match('/^session([0-9])+$/', $key, $match)) {
                $selected_sessions[] = (int) $match[1];
            }
        }
        CRM_Remoteevent_BAO_Session::setParticipantRegistrations($this->participant_id, $selected_sessions);

        // set status
        CRM_Core_Session::setStatus(
            E::ts("Session Registrations were updated"),
            E::ts("Participant Registration"),
            'info'
        );

        parent::postProcess();
    }


    /**
     * Inject session information into the participant view
     *
     * @param CRM_Event_Page_Tab $page
     */
    public static function injectSessionsInfo($page)
    {
        if (!empty($page->_id) && ($page instanceof CRM_Event_Page_Tab)) {
            try {
                $participant_id = (int) $page->_id;
                $event_id = (int) civicrm_api3('Participant', 'getvalue', [
                    'id'     => $participant_id,
                    'return' => 'event_id']);
                $has_sessions = CRM_Core_DAO::singleValueQuery(
                    "SELECT id FROM civicrm_session WHERE event_id = {$event_id} LIMIT 1");
                if ($has_sessions) {
                    // find registrations
                    $registrations = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($participant_id);

                    // load session names
                    $session_names = [];
                    if (!empty($registrations)) {
                        $sessions = civicrm_api3('Session', 'get', [
                            'id'           => ['IN' => $registrations],
                            'return'       => 'title',
                            'option.limit' => 0
                        ]);
                        foreach ($sessions['values'] as $session) {
                            $session_names[] = $session['title'];
                        }
                    }

                    Civi::resources()->addVars('remoteevent_participant_sessions', [
                        'sessions'      => $registrations,
                        'session_count' => count($registrations),
                        'label'         => E::ts("Sessions"),
                        'value_title'   => empty($registrations) ? E::ts("No Session Registrations") :
                                                    implode(', ', $session_names),
                        'value_text'    => empty($registrations) ? E::ts("No Session Registrations") :
                            E::ts("Registered for %1 Sessions", [1 => count($registrations)]),
                        'link'          => CRM_Utils_System::url('civicrm/event/participant/sessions',
                                                    "participant_id={$participant_id}&reset=1"),
                        ]);
                    Civi::resources()->addScriptUrl(E::url('js/participant_session_snippet.js'));
                }
            } catch (Exception $ex) {
                Civi::log()->debug("Error while checking for participant sessions: " . $ex->getMessage());
            }
        }
    }
}
