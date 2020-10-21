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
 * Class GetParticipantFormEventBase
 *
 * This is the base event for the three GetParticipantFormEvents
 */
abstract class GetParticipantFormEventBase extends RemoteEvent
{

    /** @var array holds the original RemoteParticipant.get_form parameters */
    protected $params;

    /** @var array holds the event data of the event involved */
    protected $event;

    /** @var array holds the RemoteParticipant.get_form result to be modified/extended */
    protected $result;

    /** @var integer will hold the contact ID once/if identified via remote_contact_id or token */
    protected $contact_id;

    /** @var integer will hold the participant ID once/if identified via token */
    protected $participant_id;

    public function __construct($params, $event)
    {
        $this->params = $params;
        $this->event  = $event;
        $this->result = [];
        $this->contact_id = null;
        $this->participant_id = null;
    }

    /**
     * Get the token usage key for this event type
     *
     * @return string
     */
    abstract protected function getTokenUsage();

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get the participant ID, usually based on the invite_token
     *
     * @return integer
     *    the participant identified to have issued the request
     */
    public function getParticipantID()
    {
        if ($this->participant_id === null) {
            if (!empty($this->params['token'])) {
                // there is a token, see if it complies with the known formats
                $participant_id = \CRM_Remotetools_SecureToken::decodeEntityToken(
                    'Participant',
                    $this->params['token'],
                    'invite'
                );
                if ($participant_id) {
                    $this->participant_id = $participant_id;
                } else {
                    $this->participant_id = 0; // don't look it up again
                }
            }
        }

        return $this->participant_id;
    }

    /**
     * Get the contact ID, usually based on the remote_contact_id or invite_token
     *
     * @return integer
     *    the contact identified to have issued the request
     */
    public function getContactID()
    {
        if ($this->contact_id === null) {
            // do some lookups
            if (!empty($this->params['token'])) {
                // there is a token, see if it complies with the known formats

                // if there is a participant token, use its contact
                $participant_id = $this->getParticipantID();
                if ($participant_id) {
                    $contact_id = civicrm_api3('Participant','getvalue', [
                            'id' => $participant_id,
                            'return' => 'contact_id']);
                    if ($contact_id) {
                        $this->setContactID($contact_id);
                    }

                } else {
                    // see if there is contact token, use that
                    $contact_id = \CRM_Remotetools_SecureToken::decodeEntityToken(
                        'Contact',
                        $this->params['token'],
                        'invite'
                    );
                    if ($contact_id) {
                        $this->setContactID($contact_id);
                    }
                }
            }

            // if this is still null (i.e. we haven't looked into this), check the remote contact_id
            if ($this->contact_id === null) {
                if (!empty($this->params['remote_contact_id'])) {
                    // only use remote_contact_id if no invite presented
                    $contact_id = \CRM_Remotetools_Contact::getByKey($this->params['remote_contact_id']);
                    if ($contact_id) {
                        $this->setContactID($contact_id);
                    }
                }
            }

            // mark as $contact_id 'we tried' (by using 0 instead of null) to prevent doing this lookup again
            if ($this->contact_id === null) {
                $this->setContactID(0);
            }
        }

        return $this->contact_id;
    }

    /**
     * Set the contact ID
     */
    public function setContactID($contact_id)
    {
        // todo: logging for debugging?
        $this->contact_id = $contact_id;
    }

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function &getResult()
    {
        return $this->result;
    }

    /**
     * Add a number of field specs to the result
     *
     * @param array $field_list
     *   fields to add
     */
    public function addFields($field_list)
    {
        foreach ($field_list as $key => $field_spec) {
            $this->result[$key] = $field_spec;
        }
    }

    /**
     * Add a current/default value to the given field
     *
     * @param string $field_name
     *   field name / key
     *
     * @param string $value
     *  default/current value to be submitted for form prefill
     */
    public function setPrefillValue($field_name, $value)
    {
        $this->result[$field_name]['value'] = $value;
    }

    /**
     * Get the parameters of the original query
     *
     * @return array
     *   parameters of the query
     */
    public function getQueryParameters()
    {
        return $this->params;
    }
}
