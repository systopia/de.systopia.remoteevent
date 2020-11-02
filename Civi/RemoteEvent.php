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


namespace Civi;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class RemoteEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * Abstract event class to provide some basic functions
 */
abstract class RemoteEvent extends Event
{
    /** @var integer participant ID */
    protected $participant_id = null;

    /** @var integer contact ID */
    protected $contact_id = null;

    /** @var integer event ID */
    protected $event_id = null;

    /** @var array accepted usage for tokes */
    protected $token_usages = ['invite'];


    /**
     * Get the parameters of the original query
     *
     * @return array
     *   parameters of the query
     */
    public abstract function getQueryParameters();

    /**
     * Add a debug message to the event, so it's easier to find out what happened
     *
     * @param string $message
     *  the debug message
     *
     * @param string $origin
     *  where does this message come from. defaults to file:line_nr
     */
    public function logMessage($message, $origin = null) {
        if (!$origin) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
            $origin = $backtrace[0]['file'];
        }

        // todo: collect? return?
        \Civi::log()->debug("RemoteEvent({$origin}): {$message}");
    }

    /**
     * Get the participant associated with this validation
     *  (if any)
     */
    public function getParticipantID()
    {
        if ($this->participant_id === null) {
            $query = $this->getQueryParameters();
            if (!empty($query['token'])) {
                // there is a token, see if it complies with the known formats
                foreach ($this->token_usages as $token_usage) {
                    $participant_id = \CRM_Remotetools_SecureToken::decodeEntityToken(
                        'Participant',
                        $query['token'],
                        $token_usage
                    );
                    if ($participant_id) {
                        $this->participant_id = $participant_id;
                        break;
                    } else {
                        $this->participant_id = 0; // don't look it up again
                    }
                }

                // also check for Contact Tokens
                $contact_id = $this->getContactID();

            }
        }

        return $this->participant_id;
    }

    public function getContactID()
    {
        if ($this->contact_id === null) {
            $this->contact_id = 0; // don't look up again

            $query = $this->getQueryParameters();
            if (!empty($query['token'])) {
                // there is a token, see if it complies with the known formats
                foreach ($this->token_usages as $token_usage) {
                    $contact_id = \CRM_Remotetools_SecureToken::decodeEntityToken(
                        'Contact',
                        $query['token'],
                        $token_usage
                    );
                    if ($contact_id) {
                        $this->contact_id = $contact_id;
                        break;
                    }
                }
            }

            // check if there is a participant
            $participant_id = $this->getParticipantID();
            if ($participant_id) {
                $this->contact_id = civicrm_api3('Participant', 'getvalue', [
                    'id'     => $participant_id,
                    'return' => 'contact_id']);
            }

            // last resort (tokens take precedence): via remote_contact_id
            if (empty($this->contact_id)) {
                $this->contact_id = $this->getRemoteContactID();
            }
        }

        return $this->contact_id;
    }

    /**
     * Get the ID of the event involved
     */
    public function getEventID() {
        if ($this->event_id === null) {
            $this->event_id = 0; // don't look up again

            $params = $this->getQueryParameters();
            if (!empty($params['event_id'])) {
                $this->event_id = (int) $params['event_id'];
            }

            if (!$this->event_id) {
                // event ID not given, try via participant
                $participant_id = $this->getParticipantID();
                if ($participant_id) {
                    $this->event_id = civicrm_api3('Participant', 'getvalue', [
                        'id'     => $participant_id,
                        'return' => 'event_id'
                    ]);
                }
            }
        }

        return $this->event_id;
    }

    /**
     * Get the contact ID if a valid remote_contact_id is involved with this event
     *
     * Warning: this function is cached
     *
     * @return integer|null
     *   the contact ID if a valid id was passed
     */
    public function getRemoteContactID()
    {
        static $remote_contact_lookup_cache = [];
        $data = $this->getQueryParameters();
        if (empty($data['remote_contact_id'])) {
            return null;
        } else {
            $remote_contact_key = $data['remote_contact_id'];
            if (!array_key_exists($remote_contact_key, $remote_contact_lookup_cache)) {
                // do the lookup
                $remote_contact_lookup_cache[$remote_contact_key] = \CRM_Remotetools_Contact::getByKey($data['remote_contact_id']);
            }
            return $remote_contact_lookup_cache[$remote_contact_key];
        }
    }

    /**
     * Get the currently used locale
     *
     * @return \CRM_Remoteevent_Localisation
     *   the currently used localisation object
     */
    public function getLocalisation()
    {
        $data = $this->getQueryParameters();
        if (empty($data['locale'])) {
            return \CRM_Remoteevent_Localisation::getLocalisation('default');
        } else {
            return \CRM_Remoteevent_Localisation::getLocalisation($data['locale']);
        }
    }
}
