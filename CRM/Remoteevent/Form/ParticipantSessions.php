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
        $this->event = civicrm_api3('Event', 'getsingle', ['id' => $this->participant['event_id']]);
        $this->sessions = CRM_Remoteevent_BAO_Session::getSessions($this->participant['event_id']);
        $this->registrations = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($this->participant_id);

        // set titles
        $this->setTitle(E::ts("Sessions for '%1'", [1 => $this->participant['display_name']]));
        $this->assign('event_header', E::ts("Event is '%1'", [1 => $this->participant['event_title']]));
        Civi::resources()->addStyleUrl(E::url('css/event_sessions.css'));

        // build form
        $this->add(
            'checkbox',
            'bypass_restriction',
            E::ts("Bypass restrictions")
        );

        // load days
        $event_days = CRM_Remoteevent_Form_EventSessions::getEventDays($this->event);
        $this->assign('event_days', $event_days);
        $this->assign('is_single_day', $event_days == 1);

        // load slots
        $slots = CRM_Remoteevent_Form_EventSessions::getSlots();
        $this->assign('slots', $slots);

        // load sessions
        $this->assign('sessions', CRM_Remoteevent_Form_EventSessions::getSessionList($event_days, $this->event['id']));

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

        Civi::resources()->addStyleUrl(E::url('css/participant_sessions.css'));
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
        Civi::log()->debug("VALUES: " . json_encode($values));
        // update selectes sessions
        $selected_sessions = [];
        foreach ($values as $key => $value) {
            if (!empty($value) && preg_match('/^session([0-9]+)$/', $key, $match)) {
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
     * Renders a list of all sessions for this event
     *  they will be grouped by day and slot
     *
     * @param array $event_days
     *   list of days the event has
     *
     * @return array
     *   session items by slot
     */
    protected function getSessionList($event_days)
    {
        $event_days = self::getEventDays($event);
        $sessions_by_day_and_slot = [];
        foreach ($event_days as $event_day) {
            $sessions_by_day_and_slot[$event_day] = [];
        }

        // load all sessions
        $sessions = CRM_Remoteevent_BAO_Session::getSessions($this->_id);

        // sort into days and slots
        foreach ($sessions as $session) {
            $session_day = date('Y-m-d', strtotime($session['start_date']));
            $slot = empty($session['slot_id']) ? 'no_slot' : $session['slot_id'];
            $sessions_by_day_and_slot[$session_day][$slot][] = $session;
        }

        // beautify / improve list
        $categories = $this->getCategories();
        $types = $this->getTypes();
        // rename days
        foreach (array_keys($sessions_by_day_and_slot) as $day_index => $day) {
            $new_key = E::ts("Day %1 (%2)", [
                1 => $day_index + 1,
                2 => date('d.m.Y', strtotime($day))]);
            $sessions_by_day_and_slot[$new_key] = $sessions_by_day_and_slot[$day];
            unset($sessions_by_day_and_slot[$day]);
        }

        // enrich data
        $participant_counts = CRM_Remoteevent_BAO_Session::getParticipantCounts($this->_id);
        foreach ($sessions_by_day_and_slot as $day => &$day_slots) {
            foreach ($day_slots as $slot => &$sessions) {
                foreach ($sessions as &$session) {
                    $icons = $classes = $actions = [];

                    // format start- and end date
                    $start_time = date(E::ts("H:i"), strtotime($session['start_date']));
                    $end_time = date(E::ts("H:i"), strtotime($session['end_date']));
                    $session['time'] = E::ts("%1h - %2h", [1 => $start_time, 2 => $end_time]);
                    $minutes = (int) (strtotime($session['end_date']) - strtotime($session['start_date'])) / 60.0;
                    $session['duration'] = E::ts("%1 minutes", [1 => $minutes]);

                    // add description
                    $session['description_text'] = CRM_Utils_String::htmlToText(
                        CRM_Utils_Array::value('description', $session, ''));
                    if (empty($session['description_text'])) {
                        $session['description_text'] = E::ts("No description");
                    }

                    // resolve type and category
                    $session['category'] = CRM_Utils_Array::value($session['category_id'], $categories, E::ts('None'));
                    $session['type'] = CRM_Utils_Array::value($session['type_id'], $types, E::ts('None'));

                    // participant count
                    $session['participant_count'] = CRM_Utils_Array::value($session['id'], $participant_counts, 0);
                    if (!empty($session['max_participants'])) {
                        $session['participants'] = E::ts("%1 / %2", [
                            1 => $session['participant_count'],
                            2 => $session['max_participants']]);
                        // check if full
                        if ($session['participant_count'] >= $session['max_participants']) {
                            $message = E::ts("Session is full");
                            $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-lock\" aria-hidden=\"true\"></i>";
                            $classes[] = " remote-session-max-participants remote-session-full ";
                        } else {
                            $message = E::ts("Session restricted to %1 participants", [1 => $session['max_participants']]);
                            $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-unlock-alt\" aria-hidden=\"true\"></i>";
                            $classes[] = " remote-session-max-participants ";
                        }
                    } else {
                        $session['participants'] = $session['participant_count'];
                    }

                    // add presenter icon
                    if (!empty($session['presenter_id'])) {
                        $presenter = $this->getPresenterString($session['presenter_id']);
                        if (empty($session['presenter_title'])) {
                            $message = E::ts("Given by %1", [1 => $presenter]);
                        } else {
                            $message = E::ts("%1 is %2", [1 => $session['presenter_title'], 2 => $presenter]);
                        }
                        $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-slideshare\" aria-hidden=\"true\"></i>";
                    }

                    // add inactive icon
                    if (empty($session['is_active'])) {
                        $message = E::ts("This session is disabled");
                        //$icons[] = "<i title=\"{$message}\" class=\"crm-i fa-toggle-off\" aria-hidden=\"true\"></i>";
                        $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-times\" aria-hidden=\"true\"></i>";
                        $classes[] = " remote-session-disabled disabled ";
                    }

                    // add location icon
                    if (!empty($session['location'])) {
                        $message = CRM_Utils_String::htmlToText($session['location']);
                        $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-map-marker\" aria-hidden=\"true\"></i>";
                        $classes[] = " remote-session-location ";
                    }

                    // add edit action
                    $edit_link = CRM_Utils_System::url("civicrm/event/session", "session_id={$session['id']}");
                    $edit_text = E::ts("edit");
                    $actions[] = "<a href=\"{$edit_link}\" class=\"action-item crm-popup crm-hover-button\">{$edit_text}</a>";

                    // add delete action
                    $delete_text = E::ts("delete");
                    $actions[] = "<a href=\"#\" onClick=\"remote_session_delete({$session['id']},false);\" class=\"action-item crm-hover-button\">{$delete_text}</a>";

                    // add list action
                    if (!empty($session['participant_count'])) {
                        $list_link = CRM_Utils_System::url("civicrm/event/session/participant_list", "session_id={$session['id']}");
                        $edit_text = E::ts("Participants");
                        $actions[] = "<a href=\"{$list_link}\" class=\"action-item crm-popup crm-hover-button\">{$edit_text}</a>";
                    }

                    // finally, store UI stuff in the session
                    $session['icons'] = $icons;
                    $session['classes'] = $classes;
                    $session['actions'] = $actions;
                }
            }
        }

        return $sessions_by_day_and_slot;
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
