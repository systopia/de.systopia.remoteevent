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
 * Class CancelEvent
 *
 * @package Civi\RemoteParticipant\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteParticipant.submit API call, so the various stages can be applied
 */
class CancelEvent extends ChangingEvent
{
    /** @var array holds the original RemoteParticipant.submit data */
    protected $submission;

    /** @var array holds the participants originally identified  */
    protected $participants_identified;

    /** @var array holds the participants that will be cancelled  */
    protected $cancellation_calls;

    /** @var array holds a list of (minor) errors */
    protected $error_list;

    public function __construct($submission_data, $participants)
    {
        $this->submission  = $submission_data;
        $this->participants_identified = $participants;
        $this->error_list = [];

        // init cancellation calls with a sensible set
        $this->cancellation_calls = [];
        foreach ($this->participants_identified as $participant) {
            if ($this->participantCanBeCancelled($participant)) {
                $this->cancellation_calls[] = [
                    'id' => $participant['id'],
                    'participant_status_id' => 4, // Cancelled
                ];
            }
        }
    }

    /**
     * Get the (editable) list of participants that were are identified by this call
     *
     * @return array
     *   list of participant data sets
     */
    public function &getParticipantsIdentified()
    {
        return $this->participants_identified;
    }

    /**
     * Get the (editable) list of participants that should be cancelled
     *
     * @return array
     *   list of participant data sets
     */
    public function &getParticipantCancellations()
    {
        return $this->cancellation_calls;
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

    /**
     * Do the standard checks if this participant _can_ be cancelled
     *
     * @param array $participant
     *   participant data
     *
     * @return boolean
     */
    public function participantCanBeCancelled($participant)
    {
        // check if status is not (already) negative
        $all_statuses = \CRM_Remoteevent_Registration::getParticipantStatusList();
        $participant_status = \CRM_Utils_Array::value($participant['participant_status_id'], $all_statuses);
        if (empty($participant_status)) {
            $this->addError("Participant [{$participant['id']}] has no valid status");
            return false;
        } else {
            if ($participant_status['class'] == 'Negative') {
                $this->addError("Participant [{$participant['id']}] already has a negative status");
                return false;
            }
        }

        // check if event is still active
        $event = \CRM_Remoteevent_EventCache::getEvent($participant['event_id']);
        if (empty($event['is_active'])) {
            $this->addError("Participant [{$participant['id']}] belongs to an inactive event");
            return false;
        }

        // check if cancellation is active
        if (empty($event['allow_selfcancelxfer'])) {
            $this->addError("Event of participant [{$participant['id']}] does not allow cancellations");
            return false;
        }

        // check if cancellation is active and within time-frame
        $hours_before_allowed = (int) $event['selfcancelxfer_time'];
        $hours_before = (strtotime($event['event_start_date']) - strtotime('now')) / 60 / 60;
        if ($hours_before < $hours_before_allowed) {
            $this->addError("Event of participant [{$participant['id']}] does not allow cancellation {$hours_before} hours before event");
            return false;
        }

        return true;
    }
}
