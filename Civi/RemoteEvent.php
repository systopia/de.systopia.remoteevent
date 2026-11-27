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

declare(strict_types = 1);

namespace Civi;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class RemoteEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * Abstract event class to provide some basic functions
 */
// phpcs:ignore Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class RemoteEvent extends RemoteToolsRequest {
  /**
   * @var integer participant ID */
  protected $participant_id = NULL;

  protected ?int $contact_id = NULL;

  /**
   * @phpstan-var array<string, mixed>|null
   */
  protected ?array $event = NULL;

  /**
   * @var integer event ID */
  protected $event_id = NULL;

  /**
   * @var array context data */
  protected $context_data = [];

  /**
   * @var array accepted usage for tokes */
  protected $token_usages = ['invite'];

  public function __construct(array $original_request = [], ?array $event = NULL) {
    parent::__construct($original_request);
    $this->event = $event;
  }

  /**
   * Get the parameters of the original query
   *
   * @return array
   *   parameters of the query
   */
  abstract public function getQueryParameters();

  /**
   * Get the context of this validation:
   * 'create', 'update', 'cancel'
   */
  public function getContext() {
    return $this->getQueryParameters()['context'] ?? 'create';
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
  public function logMessage($message, $origin = NULL) {
    if (!$origin) {
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
      $origin = $backtrace[0]['file'];
    }

    \Civi::log()->debug("RemoteEvent({$origin}): {$message}");
  }

  /**
   * Retrieves the ID of an existing participant.
   *
   * @return int|null
   *   The participant ID, or NULL if this is a new participant.
   */
  public function getParticipantID(): ?int {
    if ($this->participant_id === NULL) {
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
            $this->participant_id = (int) $participant_query['id'];
          }
          else {
            // don't look it up again
            $this->participant_id = 0;
          }
        }
      }
    }

    return (int) $this->participant_id;
  }

  public function getContactID() {
    if ($this->contact_id === NULL) {
      // don't look up again
      $this->contact_id = 0;

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
        $this->contact_id = (int) civicrm_api3('Participant', 'getvalue', [
          'id'     => $participant_id,
          'return' => 'contact_id',
        ]);
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
    if ($this->event_id === NULL) {
      // don't look up again
      $this->event_id = 0;

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
            'return' => 'event_id',
          ]);
        }
      }
    }

    return $this->event_id;
  }

  /**
   * Get the given event data
   *
   * @phpstan-return array<string, mixed>|null
   *   event data
   */
  public function getEvent() {
    if (NULL === $this->event) {
      $event_id = $this->getEventID();
      if ($event_id) {
        $this->event = \CRM_Remoteevent_RemoteEvent::getRemoteEvent($event_id);
      }
    }

    return $this->event;
  }

  /**
   * Get the contact ID if a valid remote_contact_id is involved with this event
   *
   * Warning: this function is cached
   *
   * @return integer|null
   *   the contact ID if a valid id was passed
   */
  public function getRemoteContactID() {
    static $remote_contact_lookup_cache = [];
    $data = $this->getQueryParameters();
    if (empty($data['remote_contact_id'])) {
      return NULL;
    }
    else {
      $remote_contact_key = $data['remote_contact_id'];
      if (!array_key_exists($remote_contact_key, $remote_contact_lookup_cache)) {
        // do the lookup
        $remote_contact_lookup_cache[$remote_contact_key] = \CRM_Remotetools_Contact::getByKey(
          $data['remote_contact_id']
        );
      }
      return (int) $remote_contact_lookup_cache[$remote_contact_key];
    }
  }

  /**
   * Get the currently relevant locale
   */
  public function getLocale() {
    $data = $this->getQueryParameters();
    if (empty($data['locale'])) {
      return 'default';
    }
    else {
      return $data['locale'];
    }
  }

  /**
   * Get the currently used locale
   *
   * @return \CRM_Remoteevent_Localisation
   *   the currently used localisation object
   */
  public function getLocalisation() {
    $data = $this->getQueryParameters();
    if (empty($data['locale'])) {
      return \CRM_Remoteevent_Localisation::getLocalisation('default');
    }
    else {
      return \CRM_Remoteevent_Localisation::getLocalisation($data['locale']);
    }
  }

  /**
   * Localise the string with the currently active localisation
   *
   * @param string $string
   *    string to be localised
   *
   * @param array $context
   *
   * @return string
   *   localised string
   */
  public function localise(string $string, array $context = []): string {
    return $this->getLocalisation()->ts($string, $context);
  }

  /**
   * Allows the event users to add some arbitrary context data
   *
   * @param string $key
   *   key for the context data
   * @param mixed $value
   *   any type of data
   */
  public function setContextData($key, $value) {
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
  public function getContextData($key, $default = NULL) {
    return $this->context_data[$key] ?? $default;
  }

  /**
   * Generate an API3 success result.
   *
   * @param array<string, mixed>|int $values
   * @param array<string, mixed> $extraReturnValues
   * @param array<string, mixed> $params
   *
   * @return array<string, mixed>
   */
  public function createAPI3Success(
    string $entity,
    string $action,
    array|int $values = [],
    array $extraReturnValues = [],
    array $params = []
  ): array {
    // add status messages
    $extraReturnValues['status_messages'] = $this->getStatusMessageList();

    // compile standard result
    static $null = NULL;
    return civicrm_api3_create_success($values, $params, $entity, $action, $null, $extraReturnValues);
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
  public function overrideParticipant($particiant_id) {
    $previous_participant_id = $this->participant_id;
    $this->participant_id = $particiant_id;
    return $previous_participant_id;
  }

}
