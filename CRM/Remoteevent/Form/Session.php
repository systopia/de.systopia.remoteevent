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
 * Session Editor
 */
class CRM_Remoteevent_Form_Session extends CRM_Core_Form
{
    const SESSION_PROPERTIES = [
        'title',
        'start_date',
        'end_date',
        'slot_id',
        'type_id',
        'category_id',
        'description',
        'location',
        'max_participants',
        'presenter_title',
        'presenter_id',
        'is_active',
    ];

    /** @var string session id or null for new one */
    protected $session_id = null;

    /** @var string session id or null for new one */
    protected $event_id = null;

    public function buildQuickForm()
    {
        $this->session_id = CRM_Utils_Request::retrieve('session_id', 'String', $this);
        $this->event_id = CRM_Utils_Request::retrieve('event_id', 'String', $this);

        // set title
        if ($this->getSessionID()) {
            $event_title = civicrm_api3('Event', 'getvalue', [
                'id'     => $this->getEventID(),
                'return' => 'title']);
            $this->setTitle(E::ts("Edit Session [%1] for Event '%2'", [
                1 => $this->getSessionID(),
                2 => $event_title])
            );
        } else {
            if ($this->getEventID()) {
                $event_title = civicrm_api3('Event', 'getvalue', [
                    'id'     => $this->getEventID(),
                    'return' => 'title']);
                $this->setTitle(E::ts("Creating new Session for Event '%1", [
                    1 => $event_title])
                );
            }
        }

        $this->add(
            'checkbox',
            'is_active',
            E::ts("Session Active"),
            []
        );

        $this->add(
            'text',
            'title',
            E::ts("Session Title"),
            ['class' => 'huge'],
            true
        );

        $this->add(
            'datepicker',
            'start_date',
            E::ts("Session Start"),
            [],
            true,
            ['time' => true]
        );

        $this->add(
            'datepicker',
            'end_date',
            E::ts("Session End"),
            [],
            true,
            ['time' => true]
        );

        $this->add(
            'select',
            'slot_id',
            E::ts("Session Slot"),
            $this->getOptionValues('session_slot', "No Slot"),
            false,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'type_id',
            E::ts("Session Type"),
            $this->getOptionValues('session_type'),
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'category_id',
            E::ts("Session Category"),
            $this->getOptionValues('session_category'),
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'wysiwyg',
            'description',
            E::ts('Session Description'),
            ['class' => 'collapsed'],
            false
        );

        $this->add(
            'wysiwyg',
            'location',
            E::ts('Session Location'),
            ['class' => 'collapsed'],
            false
        );

        $this->add(
            'text',
            'max_participants',
            E::ts('Maximum Participants'),
            ['class' => 'tiny'],
            false
        );

        $this->add(
            'text',
            'presenter_title',
            E::ts('Presenter Title'),
            ['class' => 'short'],
            false
        );

        $this->addEntityRef(
            'presenter_id',
            E::ts('Presenter'),
            [
                'entity' => 'Contact',
                'api' => ['params' => ['is_deleted' => 0, 'limit' => 20]],
            ],
            false
        );

        $this->addButtons(
            [
                [
                    'type' => 'submit',
                    'name' => $this->getSessionID() ? E::ts('Update') :  E::ts('Create'),
                    'isDefault' => true,
                ],
            ]
        );

        // set defaults
        $session_id = $this->getSessionID();
        if ($session_id) {
            try {
                $defaults = [];
                $current_values = civicrm_api3('Session', 'getsingle', ['id' => $session_id]);
                foreach (self::SESSION_PROPERTIES as $property) {
                    $defaults[$property] = CRM_Utils_Array::value($property, $current_values);
                }
                $this->setDefaults($defaults);
            } catch (CRM_Core_Exception $ex) {
                throw new CRM_Core_Exception("Session [{$session_id}] does not exist");
            }
        } else {
            $event_id = $this->getEventID();
            if ($event_id) {
                $event = civicrm_api3('Event', 'getsingle', ['id' => $this->getEventID()]);
                $this->setDefaults([
                    // set both start/end date to the first day (for now)
                    // todo: maybe a "day of event" selector at some point?
                    'start_date' => $event['start_date'],
                    'end_date'   => $event['start_date'],
                ]);
            }
        }

        parent::buildQuickForm();
    }

    /**
     * Do some input validation
     *
     * @return bool
     */
    public function validate()
    {
        parent::validate();

        $event = $this->getEvent();

        // verify the start date is not before the event's start date
        if (strtotime($this->_submitValues['start_date']) < strtotime($event['start_date'])) {
            $this->_errors['start_date'] = E::ts("The start date of the session should not be before the start of the event.");
        }

        // verify the end date is not after the event's end date
        if (strtotime($this->_submitValues['end_date']) > strtotime($event['end_date'])) {
            $this->_errors['end_date'] = E::ts("The end date of the session should not be after the end of the event.");
        }

        // verify the start date is not after the end date
        if (strtotime($this->_submitValues['start_date']) > strtotime($this->_submitValues['end_date'])) {
            $this->_errors['end_date'] = E::ts("The end date of the session cannot be after the start date.");
        }

        return (0 == count($this->_errors));
    }

    public function postProcess()
    {
        // simply collect the values and write out
        $values = $this->exportValues();
        $update = [];
        foreach (self::SESSION_PROPERTIES as $property) {
            $update[$property] = CRM_Utils_Array::value($property, $values);
        }

        $session_id = $this->getSessionID();
        if ($session_id) {
            $update['id'] = $session_id;
        } else {
            $update['event_id'] = $this->getEventID();
        }

        // postprocess some values
        if (empty($update['presenter_id'])) {
          $update['presenter_id'] = '';
        }
        if (empty($update['max_participants'])) {
            $update['max_participants'] = 0;
        }
        if (empty($update['is_active'])) {
            $update['is_active'] = 0;
        }

        civicrm_api3('Session', 'create', $update);

        parent::postProcess();
    }

    /**
     * Get a list of all available categories
     * @return array
     *   list of slot_id -> slot label
     */
    protected function getOptionValues($option_group_name, $none_option = null)
    {
        $values = [];
        if ($none_option) {
            $values[''] = $none_option;
        }
        $value_query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => $option_group_name,
            'option.limit'    => 0,
            'option.sort'     => 'weight asc',
            'return'          => 'value,label'
        ]);
        foreach ($value_query['values'] as $value) {
            $values[$value['value']] = $value['label'];
        }
        return $values;
    }

    /**
     * Get the ID of the session we're editing
     * @return int|null
     */
    protected function getSessionID() {
        $session_id = (int) $this->session_id;
        if ($session_id) {
            return $session_id;
        } else {
            return null;
        }
    }

    /**
     * Get the ID of the session we're editing
     * @return int|null
     */
    protected function getEventID() {
        $event_id = (int) $this->event_id;
        if ($event_id) {
            return $event_id;
        }

        // not given? maybe by session ID

        $session_id = $this->getSessionID();
        if ($session_id) {
            $this->event_id = civicrm_api3('Session', 'getvalue', ['id' => $session_id, 'return' => 'event_id']);
            return $this->event_id;
        }

        return null;
    }

    /**
     * Get the event data
     *
     * @return array|null event
     */
    protected function getEvent()
    {
        $event_id = $this->getEventID();
        if ($event_id) {
            static $my_event = null;
            if ($my_event === null || $my_event['id'] != $event_id) {
                $my_event = civicrm_api3('Event', 'getsingle', ['id' => $event_id]);
            }
            return $my_event;
        } else {
            return null;
        }
    }
}
