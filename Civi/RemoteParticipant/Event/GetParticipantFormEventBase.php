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

namespace Civi\RemoteParticipant\Event;

use Civi\RemoteEvent;

/**
 * Class GetParticipantFormEventBase
 *
 * This is the base event for the three GetParticipantFormEvents
 */
abstract class GetParticipantFormEventBase extends RemoteEvent {

  /**
   * @var array holds the original RemoteParticipant.get_form parameters */
  protected $params;

  /**
   * @var array holds the RemoteParticipant.get_form result to be modified/extended */
  protected $result;

  /**
   * @phpstan-param array<string, mixed> $params
   * @phpstan-param array<string, mixed> $event
   */
  public function __construct(array $params, array $event) {
    $this->params = $params;
    $this->event  = $event;
    $this->result = [];
    $this->contact_id = NULL;
    $this->participant_id = NULL;
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
  public function getParams() {
    return $this->params;
  }

  /**
   * @phpstan-return array<string, array<string, mixed>>
   *   Mapping of field name to field spec.
   *
   * @see addFields()
   */
  public function &getResult(): array {
    return $this->result;
  }

  /**
   * Add a number of field specs to the result
   *
   * @param array $field_list
   *   fields to add
   */
  public function addFields($field_list) {
    foreach ($field_list as $key => $field_spec) {
      // Files are optional on update. If none is given, the previous one
      // is kept.
      if ($field_spec['type'] === 'File' && $this->getContext() === 'update') {
        $field_spec['required'] = 0;
      }

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
  public function setPrefillValue($field_name, $value, $auto_format = TRUE): void {
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
  public function getQueryParameters() {
    return $this->params;
  }

  /**
   * Add some standard/default fields
   */
  public function addStandardFields() {
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
        'value' => isset($query['remote_contact_id']) ? $query['remote_contact_id'] : '',
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
  public function addStandardGreeting() {
    // get some context
    $context = $this->getContext();
    $contact_id = $this->getContactID();
    $participant_id = $this->getParticipantID();

    try {
      // add a greeting for updating registrations
      if ($context == 'update' && $participant_id) {
        $contact_name = \civicrm_api3('Contact', 'getvalue', [
          'id'     => $contact_id,
          'return' => 'display_name',
        ]);
        $event = $this->getEvent();

        $l10n = $this->getLocalisation();
        $this->addStatus($l10n->ts("Welcome %1. You are modifying your registration for event '%2'", [
          1 => $contact_name,
          2 => $event['title'],
        ]));
      }

      // add a greeting for invitations
      if ($context == 'create' && $participant_id) {
        // first: find out the participant status
        $participant_status_id = (int) \civicrm_api3('Participant', 'getvalue', [
          'id'     => $participant_id,
          'return' => 'participant_status_id',
        ]);
        $participant_status = \CRM_Remoteevent_Registration::getParticipantStatusName($participant_status_id);

        switch ($participant_status) {
          case 'Invited':
            $contact_name = \civicrm_api3('Contact', 'getvalue', [
              'id'     => $contact_id,
              'return' => 'display_name',
            ]);
            $event = $this->getEvent();

            $l10n = $this->getLocalisation();
            $this->addStatus($l10n->ts("Welcome %1. You may now confirm or decline your invitation to event '%2'", [
              1 => $contact_name,
              2 => $event['title'],
            ]));
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
          'return' => 'display_name',
        ]);
        $event = $this->getEvent();

        $l10n = $this->getLocalisation();
        $this->addStatus($l10n->ts("Welcome %1. You are about to cancel your registration for the event '%2'", [
          1 => $contact_name,
          2 => $event['title'],
        ]));
      }

      // ...and else...other greetings to be set?

    }
    catch (\CRM_Core_Exception $exception) {
      \Civi::log()->debug('Error while rendering standard greeting: ' . $exception->getMessage());
    }
  }

}
