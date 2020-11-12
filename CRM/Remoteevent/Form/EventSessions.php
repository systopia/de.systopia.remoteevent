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
        $event_days = $this->getEventDays($event);
        $this->assign('event_days', $event_days);
        $this->assign('is_single_day', $event_days == 1);

        // load slots
        $slots = $this->getSlots();
        $this->assign('slots', $slots);

        // load sessions
        $this->assign('sessions', $this->getSessionList($event_days));

        // more stuff
        $this->assign('add_session_link', CRM_Utils_System::url("civicrm/event/session", "reset=1&event_id={$this->_id}"));

        parent::buildQuickForm();

        // not that kind of form :)
        $this->addButtons([]);

        // add scripts and styling
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
    protected function getEventDays($event_data)
    {
        if (empty($event_data['start_date'])) {
            throw new Exception(E::ts("Event doesn't have a start date!"));
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
    protected function getSessionList($event_days)
    {
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
        foreach ($sessions_by_day_and_slot as $day => &$day_slots) {
            foreach ($day_slots as $slot => &$sessions) {
                foreach ($sessions as &$session) {
                    $icons = $classes = $actions = [];

                    // format start- and end date
                    // todo: localise
                    $start_time = date('H:i', strtotime($session['start_date']));
                    $end_time = date('H:i', strtotime($session['end_date']));
                    $session['time'] = E::ts("%1 - %2h", [1 => $start_time, 2 => $end_time]);

                    // resolve type and category
                    $session['category'] = CRM_Utils_Array::value($session['category_id'], $categories, E::ts('None'));
                    $session['type'] = CRM_Utils_Array::value($session['type_id'], $types, E::ts('None'));

                    // participant count
                    // todo: calculate
                    $session['participant_count'] = 0;
                    if (!empty($session['max_participants'])) {
                        $session['participants'] = E::ts("%1 / %2", [
                            1 => $session['participant_count'],
                            2 => $session['max_participants']]);
                        // check if full
                        if ($session['participant_count'] >= $session['max_participants']) {
                            $message = E::ts("Session is full");
                            $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-stop-circle-o\" aria-hidden=\"true\"></i>";
                            $classes[] = "remote-session-full";
                        } else {
                            $message = E::ts("Session restricted to %1 participants", [1 => $session['max_participants']]);
                            $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-users\" aria-hidden=\"true\"></i>";
                            $classes[] = "remote-session-max-participants";
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
                        $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-user\" aria-hidden=\"true\"></i>";
                    }

                    // add location icon
                    if (!empty($session['location'])) {
                        $message = CRM_Utils_String::htmlToText($session['location']);
                        $icons[] = "<i title=\"{$message}\" class=\"crm-i fa-street-view\" aria-hidden=\"true\"></i>";
                        $classes[] = "remote-session-location";
                    }

                    // add edit action
                    $edit_link = CRM_Utils_System::url("civicrm/event/session", "session_id={$session['id']}");
                    $edit_text = E::ts("edit");
                    $actions[] = "<a href=\"{$edit_link}\" class=\"action-item crm-popup crm-hover-button\">{$edit_text}</a>";

                    // add delete action
                    $delete_text = E::ts("delete");
                    $actions[] = "<a href=\"#\" onClick=\"remote_session_delete({$session['id']},false);\" class=\"action-item crm-hover-button\">{$delete_text}</a>";

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
    protected function getSlots()
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
    protected function getCategories()
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
    protected function getTypes()
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
    protected function getPresenterString($contact_id)
    {
        // todo: caching
        return civicrm_api3('Contact', 'getvalue', ['id' => $contact_id, 'return' => 'display_name']);
    }
}
