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
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Class UpdateEvent
 *
 * @package Civi\RemoteParticipant\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteParticipant.update API call, so the various stages can be applied
 */
class UpdateEvent extends RemoteEvent
{
    /** @var array holds the original RemoteParticipant.submit data */
    protected $submission;

    /** @var array holds the participant data  */
    protected $participant;

    /** @var array holds the contact data  */
    protected $contact;

    public function __construct($submission_data)
    {
        $this->submission = $submission_data;

        // load the current participant
        $participant_id = $this->getParticipantID();
        if (empty($participant_id)) {
            $this->addError(E::ts("Participant not found."));
        } else {
            $this->participant = civicrm_api3('Participant', 'getsingle', ['id' => $participant_id]);
        }

        // load the contact
        $contact_id = $this->participant['contact_id'];
        if (empty($contact_id)) {
            $this->addError(E::ts("Contact not found."));
        } else {
            $this->contact = civicrm_api3('Contact', 'getsingle', ['id' => $participant_id]);
        }
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
     * Set the participant object
     *
     * @return array $participant
     *    participant data
     */
    public function &getContact()
    {
        return $this->contact;
    }


    public function getQueryParameters()
    {
        return $this->submission;
    }
}
