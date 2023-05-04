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
abstract class RemoteEvent extends RemoteToolsRequest
{
    /** @var integer participant ID */
    protected $participant_id = null;

    /** @var integer contact ID */
    protected $contact_id = null;

    /** @var integer event ID */
    protected $event_id = null;

    /** @var array context data */
    protected $context_data = [];

    /** @var array accepted usage for tokes */
    protected $token_usages = ['invite'];

    /** @var array holds the list of error messages */
    protected $error_list = [];

    /** @var array holds the list of warning messages */
    protected $warning_list = [];

    /** @var array holds the list of info/status messages */
    protected $info_list = [];


    /**
     * Get the parameters of the original query
     *
     * @return array
     *   parameters of the query
     */
    public abstract function getQueryParameters();

    /**
     * Get the context of this validation:
     * 'create', 'update', 'cancel'
     */
    public function getContext(){
        return \CRM_Utils_Array::value('context', $this->getQueryParameters(), 'create');
    }

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
            $this->participant_id = 0; // don't look it up again
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
                    }
                }
            }

            // if the contact is known: check if can find a participant
            if (empty($this->participant_id)) {
                $contact_id = $this->getContactID();
                if ($contact_id) {
                    // todo: how to select the relevant
                    $participant_query = civicrm_api3('Participant', 'get', [
                        'contact_id'   => $contact_id,
                        'event_id'     => $this->getEventID(),
                        'option.sort'  => 'id desc',
                        'option.limit' => 1,
                        'return'       => 'id',
                    ]);
                    if (!empty($participant_query['id'])) {
                        $this->participant_id = $participant_query['id'];
                    }
                }
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
     * Get the given event data
     *
     * @return array
     *   event data
     */
    public function getEvent()
    {
        $event_id = $this->getEventID();
        if ($event_id) {
            return \CRM_Remoteevent_RemoteEvent::getRemoteEvent($event_id);
        } else {
            return null;
        }
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
     * Get the currently relevant locale
     */
    public function getLocale()
    {
        $data = $this->getQueryParameters();
        if (empty($data['locale'])) {
            return 'default';
        } else {
            return $data['locale'];
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

    /**
     * Localise the string with the currently active localisation
     *
     * @param string $string
     *    string to be localised
     *
     * @return string
     *    localised string
     */
    public function localise($string)
    {
        return $this->getLocalisation()->localise($string);
    }

    /**
     * Allows the event users to add some arbitrary context data
     *
     * @param string $key
     *   key for the context data
     * @param mixed $value
     *   any type of data
     */
    public function setContextData($key, $value)
    {
        $this->context_data[$key] = $value;
    }

    /**
     * Get the context data for the given key
     *
     * @param string $key
     *   key for the context data
     * @param mixed $default
     *   any type of data that is returned, if no context data is set
     *
     * @return mixed
     *   the context data stored with the key, or the default
     */
    public function getContextData($key, $default = null)
    {
        return \CRM_Utils_Array::value($key, $this->context_data, $default);
    }


    // ERROR/WARNING/STATUS MESSAGES

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
     * Add an error message to the remote event context
     *
     * @param string $message
     *   the error message, localised
     *
     * @param string $reference
     *   a reference, e.g. e a field name
     */
    public function addError($message, $reference = '')
    {
        $this->error_list[] = [$message, $reference];
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
     * Check if the submission has errors
     * @return bool
     *   true if there is errors
     */
    public function hasWarnings()
    {
        return !empty($this->warning_list);
    }

    /**
     * Add a warning to the remote event context
     *
     * @param string $message
     *   the warning, localised
     *
     * @param string $reference
     *   a reference, e.g. e a field name
     */
    public function addWarning($message, $reference = '')
    {
        $this->warning_list[] = [$message, $reference];
    }

    /**
     * Add a warning to the remote event context
     *
     * @param string $message
     *   status message, localised
     *
     * @param string $reference
     *   a reference, e.g. e a field name
     */
    public function addStatus($message, $reference = '')
    {
        $this->info_list[] = [$message, $reference];
    }

    /**
     * Get a list of status messages in the following form
     * [
     *   message: the status message,
     *   severity: status|warning|error
     *   reference: (optional) message reference, e.g. field name
     */
    public function getStatusMessageList()
    {
        $messages = [];
        foreach ($this->error_list as $error) {
            $messages[] = [
                'message' => $error[0],
                'severity' => 'error',
                'reference' => $error[1]
            ];
        }
        foreach ($this->warning_list as $warning) {
            $messages[] = [
                'message' => $warning[0],
                'severity' => 'warning',
                'reference' => $warning[1]
            ];
        }
        foreach ($this->info_list as $info) {
            $messages[] = [
                'message' => $info[0],
                'severity' => 'status',
                'reference' => $info[1]
            ];
        }
        return $messages;
    }

    /**
     * Get in indexed array of all status messages (of the given classes)
     *   indexed by reference
     *
     * @param string[] $classes
     *   list of classes to consider
     *
     * @return array
     *  [reference => message/error] list
     */
    public function getReferencedStatusList($classes = ['error'])
    {
        $result = [];
        foreach ($this->getStatusMessageList() as $message) {
            if (in_array($message['severity'], $classes) && !empty($message['reference'])) {
                $result[$message['reference']] = $message['message'];
            }
        }
        return $result;
    }

    /**
     * Generate an API3 error
     */
    public function createAPI3Error()
    {
        $first_error = reset($this->error_list);
        return civicrm_api3_create_error($first_error[0], ['status_messages' => $this->getStatusMessageList()]);
    }

    /**
     * Generate an API3 error
     */
    public function createAPI3Success($entity, $action, $values = [], $extraReturnValues = [], $params = [])
    {
        // add status messages
        $extraReturnValues['status_messages'] = $this->getStatusMessageList();

        // compile standard result
        static $null = null;
        return civicrm_api3_create_success($values, $params, $entity, $action, $null, $extraReturnValues);
    }

    /**
     * Generate a RemoteEvent conform API3 error
     *
     * @param $error_message
     *
     *
     */
    public static function createStaticAPI3Error($error_message)
    {
        return civicrm_api3_create_error($error_message, [
            'status_messages' => [
                [
                    'message' => $error_message,
                    'severity' => 'error',
                    'reference' => '',
                ]
            ]
        ]);
    }

    /**
     * Allows the override of the participant ID.
     *   Use with caution, a regular workflow driven be a API call
     *   should not need to use this
     *
     * @param integer $particiant_id
     *   override the participant_id
     *
     * @return integer
     *   previously determined participant ID
     */
    public function overrideParticipant($particiant_id)
    {
        $previous_participant_id = $this->participant_id;
        $this->participant_id = $particiant_id;
        return $previous_participant_id;
    }

}
