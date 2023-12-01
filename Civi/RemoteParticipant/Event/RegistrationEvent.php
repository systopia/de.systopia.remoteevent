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
    protected $contact_data = [];

    /** @var array holds the participant data  */
    protected $participant = [];

    /**
     * @phpstan-var array<int, array<string, mixed>>
     *   Contact data of additional participants.
     *
     * @see $additional_participants_data
     */
    protected array $additional_contacts_data = [];

    /**
     * @phpstan-var array<int, array<string, mixed>>
     *   Data of additional participants.
     *
     * @see $additional_contacts_data
     */
    protected array $additional_participants_data = [];

    public function __construct(array $submission_data)
    {
        $this->submission = $submission_data;
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
        $this->participant_id = $participant['id'] ?? null;
        $this->participant = $participant;
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

    /**
     * @phpstan-return array<int, array<string, mixed>>
     *
     * @see getAdditionalParticipantsData()
     */
    public function getAdditionalContactsData(): array
    {
        return $this->additional_contacts_data;
    }

    /**
     * @phpstan-param array<int, array<string, mixed>> $additional_contacts_data
     *
     * @see setAdditionalParticipantsData()
     */
    public function setAdditionalContactsData(array $additional_contacts_data): void
    {
        $this->additional_contacts_data = $additional_contacts_data;
    }

    /**
     * @phpstan-return array<int, array<string, mixed>>
     *
     * @see getAdditionalContactsData()
     */
    public function getAdditionalParticipantsData(): array
    {
        return $this->additional_participants_data;
    }

    /**
     * @phpstan-param array<array<string, mixed>> $additional_participants_data
     */
    public function setAdditionalParticipantsData(array $additional_participants_data): void
    {
        $this->additional_participants_data = $additional_participants_data;
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
