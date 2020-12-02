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
 *  RemoteParticipant.submit API call, so the various stages can be applied
 */
class RegistrationEvent extends ChangingEvent
{
    /** @var array holds the original RemoteParticipant.submit data */
    protected $submission;

    /** @var array holds the participant data  */
    protected $contact_data;

    /** @var array holds the participant data  */
    protected $participant;

    /** @var array holds a list of (minor) errors */
    protected $error_list;

    public function __construct($submission_data)
    {
        $this->submission  = $submission_data;
        $this->contact_id = null;
        $this->participant_id = null;
        $this->contact_data = [];
        $this->error_list = [];

        // create participant data based on submission
        $this->participant = $submission_data;
        unset($this->participant['profile'], $this->participant['remote_contact_id'], $this->participant['locale']);

        // resolve custom fields
        \CRM_Remoteevent_CustomData::resolveCustomFields($this->participant);

        // set some defaults
        if (empty($this->participant['role_id'])) {
            $event = $this->getEvent();
            if (empty($event['default_role_id'])) {
                $this->participant['role_id'] =  1; // Attendee
            } else {
                $this->participant['role_id'] =  $event['default_role_id'];
            }
        }
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
     * Get the participant ID
     *
     * @return integer
     *    contact ID
     */
    public function getParticipantID()
    {
        if (empty($this->participant['id'])) {
            $participant_id = parent::getParticipantID();
            if ($participant_id) {
                $this->participant['id'] = $participant_id;
            }
            return $participant_id;
        } else {
            return (int) $this->participant['id'];
        }
    }

    /**
     * Set the participant object
     *
     * @param array $participant
     *    participant data
     */
    public function setParticipant($participant)
    {
        $this->participant_id = $participant['id'];
        $this->participant = $participant;
    }

    /**
     * Set the participant object
     *
     * @return array $participant
     *    participant data
     */
    public function &getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set the contact_data object, which is used for
     *   contact identification / creation
     *
     * @param array $contact_data
     *    contact_data data
     */
    public function setContactData($contact_data)
    {
        $this->contact_data = $contact_data;
    }

    /**
     * Get the contact_data object, which is used for
     *   contact identification / creation
     *
     * @return array $contact_data
     *    contact_data data
     */
    public function &getContactData()
    {
        return $this->contact_data;
    }


    /**
     * Set the contact ID
     *
     * @return integer
     *    contact ID
     *
     * @throws \Exception
     *    if another contact ID has already been set
     */
    public function setContactID($contact_id)
    {
        $contact_id = (int) $contact_id;
        if ($contact_id) {
            if ($this->getContactID() && $this->getContactID() != $contact_id) {
                throw new \Exception("Conflicting contact IDs, there is a programming error");
            }
            $this->contact_id = $contact_id;
        }
    }

    /**
     * Get a submitted parameter
     *
     * @param string $value_name
     *   key of the value
     *
     * @return mixed|null
     */
    public function getSubmittedValue($value_name)
    {
        return \CRM_Utils_Array::value($value_name, $this->submission);
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
