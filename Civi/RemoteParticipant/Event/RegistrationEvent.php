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
    public const NAME = 'civi.remoteevent.registration.submit';

    /** @var array holds the original RemoteParticipant.submit data */
    protected $submission;

    /** @var array holds the participant data  */
    protected $contact_data;

    /** @var array holds the participant data  */
    protected $participant;

    /**
     * @var array
     *   Data of additional participants.
     */
    protected array $additional_participants_data = [];

  /**
   * @var array
   *   Additionally registered participants.
   */
    protected array $additional_participants = [];

    /** @var array holds a list of (minor) errors */
    protected $error_list;

    public function __construct($submission_data)
    {
        $this->submission  = $submission_data;
        $this->contact_id = null;
        $this->participant_id = null;
        $this->contact_data = [];
        $this->error_list = [];

        $event = $this->getEvent();

        // create participant data based on submission
        $this->participant = array_filter(
          $submission_data,
          function($value, $key) {
            return
              !preg_match('#^additional_([0-9]+)(_|$)#', $key)
              && !in_array($key, [
                'profile',
                'remote_contact_id',
                'locale',
              ]);
          },
          ARRAY_FILTER_USE_BOTH
        );

        // resolve custom fields
        \CRM_Remoteevent_CustomData::resolveCustomFields($this->participant);

        // set some defaults
        if (empty($this->participant['role_id'])) {
            if (empty($event['default_role_id'])) {
                $this->participant['role_id'] =  1; // Attendee
            } else {
                $this->participant['role_id'] =  $event['default_role_id'];
            }
        }

        // Create additional participants' data based on submission.
        foreach ($submission_data as $key => $value) {
            $additionalParticipantMatches =  [];
            if (preg_match('#^additional_([0-9]+)_(.*?)$#', $key, $additionalParticipantMatches)) {
                [, $participantNo, $fieldName] = $additionalParticipantMatches;
                if ($participantNo <= $event['max_additional_participants']) {
                    $this->additional_participants_data[$participantNo][$fieldName] = $value;
                }
                else {
                    throw new \Exception('Maximum number of additional participants exceeded');
                }
            }
        }
        foreach ($this->additional_participants_data as &$additional_participant) {
            \CRM_Remoteevent_CustomData::resolveCustomFields($additional_participant);
            $additional_participant['role_id'] ??= $event['default_role_id'] ?: 1; // Attendee
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
     * Sets additionally registered participant objects.
     *
     * @param array $additionalParticipants
     *
     * @return void
     */
    public function setAdditionalParticipants(array $additionalParticipants): void
    {
        $this->additional_participants = $additionalParticipants;
    }

    /**
     * Get the participant object
     *
     * @return array $participant
     *    participant data
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Retrieves additionally registered participant objects.
     *
     * @return array
     */
    public function getAdditionalParticipants()
    {
        return $this->additional_participants;
    }

    /**
     * Get the participant data BY REFERENCE, which is used for
     *   registration creation / updates
     *
     * @return array
     *    participant data
     */
    public function &getParticipantData()
    {
        return $this->participant;
    }

    public function &getAdditionalParticipantsData() {
        return $this->additional_participants_data;
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
     * Set the participant object
     *
     * @return array $participant
     *    participant data
     */
    public function getContact()
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
