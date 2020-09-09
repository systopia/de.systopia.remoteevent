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


namespace Civi\RemoteParticipant\Event;
use Civi\RemoteEvent;

/**
 * Class ValidateEvent
 *
 * @package Civi\RemoteParticipant\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteParticipant.validate API call, so the search parameters can be manipulated
 */
class ValidateEvent extends RemoteEvent
{
    /** @var array holds the original RemoteParticipant.validate submission */
    protected $submission;

    /** @var array holds the  */
    protected $error_list;

    public function __construct($submission_data, $error_list = [])
    {
        $this->submission  = $submission_data;
        $this->error_list = $error_list;
    }

    /**
     * Check if the submission has errors
     * @return bool
     *   true if there is errors
     */
    public function hasErrors()
    {
        return !empty($this->error_list);
    }

    /**
     * Get the event ID
     *
     * @return integer
     *    event ID
     */
    public function getEventID()
    {
        return (int) $this->submission['event_id'];
    }

    /**
     * Get the event data
     *
     * @return array
     *    event data
     */
    public function getEvent()
    {
        return \CRM_Remoteevent_RemoteEvent::getRemoteEvent($this->getEventID());
    }

    /**
     * Add an error to the given field
     *
     * @param string $field_name
     *   field name
     *
     * @param string $error
     *   error message to be displayed to the user
     */
    public function addError($field_name, $error)
    {
        // todo: what to do if there is already an error
        $this->error_list[$field_name] = $error;
    }

    /**
     * Get the complete submission
     *
     * @return array
     *   submission data
     */
    public function getSubmission()
    {
        return $this->submission;
    }

    /**
     * Get a list of all errors
     *
     * @return array
     *   complete error list
     */
    public function getErrors()
    {
        return $this->error_list;
    }

    /**
     * Get the parameters of the original query
     *
     * @return array
     *   parameters of the query
     */
    public function getQueryParameters()
    {
        return $this->submission;
    }
}
