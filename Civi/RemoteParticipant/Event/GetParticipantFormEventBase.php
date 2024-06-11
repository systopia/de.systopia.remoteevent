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

    public function __construct($params, $event)
    {
        $this->params = $params;
        $this->event  = $event;
        $this->result = [];
        $this->contact_id = null;
        $this->participant_id = null;
        $this->token_usages = $this->getTokenUsages();
    }

    /**
     * Get the token usage key for this event type
     *
     * @return array
     */
    abstract protected function getTokenUsages();

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
                    foreach ($this->getTokenUsages() as $usage) {
                        if ($contact_id = \CRM_Remotetools_SecureToken::decodeEntityToken(
                            'Contact',
                            $this->params['token'],
                            $usage
                        )) {
                            break;
                        }
                    }
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
     *
     * @param boolean $auto_format
     *  try to automatically form the value
     */
    public function setPrefillValue($field_name, $value, $auto_format = true): void
    {
        if (isset($this->result[$field_name])) {
            if ($auto_format) {
                $field = $this->result[$field_name];
                $value = \CRM_Remoteevent_RegistrationProfile::formatFieldValue($field, $value);
            }
            $this->result[$field_name]['value'] = $value;
        }
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

    /**
     * Add some standard/default fields
     */
    public function addStandardFields()
    {
        $query = $this->getQueryParameters();

        // add event_id field
        $this->addFields([
            'event_id' => [
                'name' => 'event_id',
                'type' => 'Value',
                'value' => $this->getEventID(),
            ],
            'remote_contact_id' => [
                 'name' => 'remote_contact_id',
                 'type' => 'Value',
                 'value' => isset($query['remote_contact_id']) ? $query['remote_contact_id'] : ''
            ],
        ]);

        // todo: implement for cancel/update
        if (empty($this->result['profile'])) {
            $this->addFields([
                 'profile' => [
                     'name' => 'profile',
                     'type' => 'Value',
                     'value' => 'OneClick',
                 ],
            ]);
        }
    }


    /**
     * Add a standard message to greet the user, if known
     */
    public function addStandardGreeting()
    {
        // get some context
        $context = $this->getContext();
        $contact_id = $this->getContactID();
        $participant_id = $this->getParticipantID();

        try {
            // add a greeting for updating registrations
            if ($context == 'update' && $participant_id) {
                $contact_name = \civicrm_api3('Contact', 'getvalue', [
                    'id'     => $contact_id,
                    'return' => 'display_name']);
                $event = $this->getEvent();

                $l10n = $this->getLocalisation();
                $this->addStatus($l10n->ts("Welcome %1. You are modifying your registration for event '%2'", [
                    1 => $contact_name,
                    2 => $event['title']]));
            }

            // add a greeting for invitations
            if ($context == 'create' && $participant_id) {
                // first: find out the participant status
                $participant_status_id = (int) \civicrm_api3('Participant', 'getvalue', [
                    'id'     => $participant_id,
                    'return' => 'participant_status_id'
                ]);
                $participant_status = \CRM_Remoteevent_Registration::getParticipantStatusName($participant_status_id);

                switch ($participant_status) {
                    case 'Invited':
                        $contact_name = \civicrm_api3('Contact', 'getvalue', [
                            'id'     => $contact_id,
                            'return' => 'display_name']);
                        $event = $this->getEvent();

                        $l10n = $this->getLocalisation();
                        $this->addStatus($l10n->ts("Welcome %1. You may now confirm or decline your invitation to event '%2'", [
                            1 => $contact_name,
                            2 => $event['title']]));
                        break;

                    default:
                        // no greeting
                        break;
                }
            }

            // add a greeting for cancellations
            if ($context == 'cancel' && $participant_id) {
                $contact_name = \civicrm_api3('Contact', 'getvalue', [
                    'id'     => $contact_id,
                    'return' => 'display_name']);
                $event = $this->getEvent();

                $l10n = $this->getLocalisation();
                $this->addStatus($l10n->ts("Welcome %1. You are about to cancel your registration for the event '%2'", [
                    1 => $contact_name,
                    2 => $event['title']]));
            }

            // ...and else...other greetings to be set?

        } catch (\CiviCRM_API3_Exception $exception) {
            \Civi::log()->debug("Error while rendering standard greeting: " . $exception->getMessage());
        }
    }
}
