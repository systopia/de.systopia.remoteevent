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


        parent::buildQuickForm();

        // not that kind of form :)
        $this->addButtons([]);
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

        return $sessions_by_day_and_slot;
    }

    /**
     * Get a list of all available slots
     * @return array
     *   list of slot_id -> slot label
     */
    protected function getSlots()
    {
        $slots = [];
        $slot_query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'session_slot',
            'option.limit'    => 0,
            'return'          => 'value,label'
        ]);
        foreach ($slot_query['values'] as $slot) {
            $slots[$slot['value']] = $slot['label'];
        }
        return $slots;
    }
}
