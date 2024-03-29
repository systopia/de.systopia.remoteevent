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

/**
 * Session Tab
 */
class CRM_Remoteevent_Form_EventSessions extends CRM_Event_Form_ManageEvent
{
    /**
     * Set variables up before form is built.
     */
    public function preProcess()
    {
        parent::preProcess();
        $this->setSelectedChild('sessions');
    }

    public function buildQuickForm()
    {
        // render sessions
        $event = civicrm_api3('Event', 'getsingle', ['id' => $this->_id]);

        // load days
        $event_days = self::getEventDays($event);
        $this->assign('event_days', $event_days);
        $this->assign('is_single_day', count($event_days) == 1);

        // load slots
        $slots = self::getSlots();
        $this->assign('slots', $slots);

        // load sessions
        $sessions = self::getSessionList($event_days, $this->_id);
        $this->assign('sessions', $sessions);

        // check if a download was requested
        $download_requested = CRM_Utils_Request::retrieveValue('download_csv', 'String');
        if (!empty($download_requested)) {
            self::downloadSessionList($sessions, $event);
        }

        // add warnings (remark: can't use warning popups, since this code is excuted twice (for some reason)
        foreach ($sessions as $event_day => $day_sessions) {
            if (!empty($day_sessions)) {
                // there is at least one session -> check for warnings
                $session_warnings = CRM_Remoteevent_EventSessions::getSessionWarnings($event);
                $this->assign('session_warnings', $session_warnings);
                break; // the warnings are for _all_ sessions
            }
        }

        // more stuff
        $this->assign('add_session_link', CRM_Utils_System::url("civicrm/event/session", "reset=1&event_id={$this->_id}"));
        $this->assign('download_session_link', CRM_Utils_System::url("civicrm/event/manage/sessions", "id={$this->_id}&download_csv=1"));

        parent::buildQuickForm();

        // not that kind of form :)
        $this->addButtons([]);

        // add scripts and styling
        $reload_link = CRM_Utils_System::url('civicrm/event/manage/sessions', "reset=1&action=update&id={$event['id']}");
        Civi::resources()->addVars('remoteevent', [
            'session_reload' => $reload_link
        ]);
        Civi::resources()->addScriptUrl(E::url('js/event_sessions.js'));
        Civi::resources()->addStyleUrl(E::url('css/event_sessions.css'));
    }


    public function postProcess()
    {
        $values = $this->exportValues();
        parent::postProcess();
    }

    /**
     * Return the list of days the event takes place on
     *
     * @return array
     *   list of Y-m-d dates
     */
    public static function getEventDays($event_data)
    {
        if (empty($event_data['start_date'])) {
            // throwing this exception can cause issues with event templates, where not having a start date is allowed..
            // throw new Exception(E::ts("Event doesn't have a start date!"));

            // simply use 'now' for the time being
            $event_data['start_date'] = 'now';
        }
        if (empty($event_data['end_date'])) {
            return [date('Y-m-d', strtotime($event_data['start_date']))];
        }

        $start_date = date('Y-m-d', strtotime($event_data['start_date']));
        $end_date = date('Y-m-d', strtotime($event_data['end_date']));

        $event_days = [$start_date];
        $current_date = $start_date;
        while ($current_date != $end_date) {
            $current_date = date('Y-m-d', strtotime("{$current_date} + 1 day"));
            $event_days[] = $current_date;
        }

        return $event_days;
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
    public static function getSessionList($event_days, $event_id)
    {
        $sessions_by_day_and_slot = [];
        foreach ($event_days as $event_day) {
            $sessions_by_day_and_slot[$event_day] = [];
        }

        // load all sessions
        $sessions = CRM_Remoteevent_BAO_Session::getSessions($event_id);

        // sort into days and slots
        foreach ($sessions as $session) {
            $session_day = date('Y-m-d', strtotime($session['start_date']));
            $slot = empty($session['slot_id']) ? 'no_slot' : $session['slot_id'];
            $sessions_by_day_and_slot[$session_day][$slot][] = $session;
        }

        // strip empty days
        foreach ($sessions_by_day_and_slot as $key => $entry) {
            if (empty($entry)) {
                unset ($sessions_by_day_and_slot[$key]);
            }
        }

        // beautify / improve list
        $categories = self::getCategories();
        $types = self::getTypes();
        // rename days
        foreach (array_keys($sessions_by_day_and_slot) as $day_index => $day) {
            $new_key = E::ts("Day %1 (%2)", [
                1 => $day_index + 1,
                2 => date('d.m.Y', strtotime($day))]);
            $new_key = \Civi\LabelEvent::renderLabel($new_key, \Civi\LabelEvent::CONTEXT_SESSION_GROUP_TITLE, [
                'day' => $day,
                'day_index' => $day_index,
                'sessions' => $sessions,
                'event_id' => $event_id,
                'sessions_by_day_and_slot' => $sessions_by_day_and_slot,
                'is_backend' => true,
            ]);
            // move to new "location"
            $my_sessions = $sessions_by_day_and_slot[$day];
            unset($sessions_by_day_and_slot[$day]);
            $sessions_by_day_and_slot[$new_key] = $my_sessions;
        }

        // enrich data
        $participant_counts = CRM_Remoteevent_BAO_Session::getParticipantCounts($event_id);
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
                        $message = self::getPresenterString($session);
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
     * Get a list of all available slots
     * @return array
     *   list of slot_id -> slot label
     */
    public static function getSlots()
    {
        $slots = ['' => E::ts("No Slot")];
        $slot_query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'session_slot',
            'option.limit'    => 0,
            'return'          => 'value,label'
        ]);
        foreach ($slot_query['values'] as $slot) {
            $slots[$slot['value']] = $slot['label'];
        }
        $slots['no_slot'] = E::ts('No Slot');
        return $slots;
    }

    /**
     * Get a list of all available categories
     * @return array
     *   list of slot_id -> slot label
     */
    public static function getCategories()
    {
        $categories = ['' => E::ts("None")];
        $category_query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'session_category',
            'option.limit'    => 0,
            'return'          => 'value,label'
        ]);
        foreach ($category_query['values'] as $category) {
            $categories[$category['value']] = $category['label'];
        }
        return $categories;
    }

    /**
     * Get a list of all available types
     * @return array
     *   list of type_id -> type label
     */
    public static function getTypes()
    {
        $types = ['' => E::ts("None")];
        $type_query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'session_type',
            'option.limit'    => 0,
            'return'          => 'value,label'
        ]);
        foreach ($type_query['values'] as $type) {
            $types[$type['value']] = $type['label'];
        }
        return $types;
    }

    /**
     * Return string representation of the presenter
     *
     * @param integer $contact_id
     *   contact ID
     *
     * @return string to be shown in UI
     *
     */
    public static function getPresenterString($session)
    {
        $presenter_string = '';
        if (!empty($session['presenter_id'])) {
            $presenter = civicrm_api3('Contact', 'getvalue', ['id' => $session['presenter_id'], 'return' => 'display_name']);
            if (empty($session['presenter_title'])) {
                $presenter_string = E::ts("Given by %1", [1 => $presenter]);
            } else {
                $presenter_string = E::ts("%1 is %2", [1 => $session['presenter_title'], 2 => $presenter]);
            }
        }
        return $presenter_string;
    }

    /**
     * Generate and download a CSV version of the session list
     *
     * @param array $sessions_by_day_and_slot
     *   the sessions as delived by ::getSessionList
     *
     * @param array $event
     *   the event data
     */
    protected static function downloadSessionList($sessions_by_day_and_slot, $event)
    {
        // generate the file data
        $csv_buffer = fopen("php://temp", "w");

        // add headers
        fputcsv($csv_buffer, [
            E::ts("ID"),
            E::ts("Day"),
            E::ts("Slot"),
            E::ts("Time"),
            E::ts("Category"),
            E::ts("Type"),
            E::ts("Title"),
            E::ts("Participants"),
            E::ts("Max Participants"),
            E::ts("Presenter"),
            E::ts("Location"),
        ]);

        // add events
        $slots = self::getSlots();
        foreach ($sessions_by_day_and_slot as $day => $day_slots) {
            foreach ($day_slots as $slot => $sessions) {
                foreach ($sessions as $session) {
                    fputcsv($csv_buffer, [
                            $session['id'],
                            $day,
                            $slots[$slot],
                            $session['time'],
                            $session['category'],
                            $session['type'],
                            $session['title'],
                            $session['participant_count'],
                            CRM_Utils_Array::value('max_participants', $session, ''),
                            self::getPresenterString($session),
                            CRM_Utils_String::htmlToText(CRM_Utils_Array::value('location', $session, '')),
                        ]
                    );
                }
            }
        }
        $csv_data = stream_get_contents($csv_buffer, -1, 0);

        // generate the download
        CRM_Utils_System::download(
            E::ts("%1 - %2 Sessions.csv", [1 => date('Y-m-d'), 2 => $event['title']]),
            'text/csv',
            $csv_data
        );
    }
}
