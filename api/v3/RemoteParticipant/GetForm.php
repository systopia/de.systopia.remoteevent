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

use CRM_Remoteevent_ExtensionUtil as E;
use Civi\RemoteEvent;
use Civi\RemoteParticipant\Event\GetCreateParticipantFormEvent as GetCreateParticipantFormEvent;
use Civi\RemoteParticipant\Event\GetUpdateParticipantFormEvent as GetUpdateParticipantFormEvent;
use Civi\RemoteParticipant\Event\GetCancelParticipantFormEvent as GetCancelParticipantFormEvent;

/**
 * RemoteEvent.get_form specification
 *   will provide the full data (fields, default values) for any
 *   of the three actions: create cancel update
 *
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_participant_get_form_spec(&$spec) {
  $spec['context']          = [
    'name'         => 'context',
    'api.default'  => 'create',
    'title'        => E::ts('Context'),
    'description'  => E::ts('Which context/action is the form for (create/cancel/update)'),
  ];
  $spec['event_id']          = [
    'name'         => 'event_id',
    'api.required' => 0,
    'title'        => E::ts('Event ID'),
    'description'  => E::ts('Internal ID of the event the registration form is needed for'),
  ];
  $spec['profile']           = [
    'name'         => 'profile',
    'api.required' => 0,
    'title'        => E::ts('Profile Name'),
    'description'  => E::ts('If omitted, the default profile is used'),
  ];
  $spec['remote_contact_id'] = [
    'name'         => 'remote_contact_id',
    'api.required' => 0,
    'title'        => E::ts('Remote Contact ID'),
    'description'  => E::ts(
            'You can submit a remote contact, in which case the fields should come with the default data'
    ),
  ];
  $spec['token'] = [
    'name'         => 'token',
    'api.required' => 0,
    'title'        => E::ts('Token'),
    'description' => E::ts(
      // phpcs:ignore Generic.Files.LineLength.TooLong
      'You can submit an invite token that can be used to identify the contact, in which case the fields should come with the default data. This takes preference over the remote_contact_id'
    ),
  ];
  $spec['locale'] = [
    'name' => 'locale',
    'api.required' => 0,
    'title' => E::ts('Locale'),
    'description' => E::ts('Locale of the field labels/etc. NOT IMPLEMENTED YET'),
  ];
}

/**
 * RemoteEvent.get_form implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
// phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
function civicrm_api3_remote_participant_get_form($params) {
  unset($params['check_permissions']);
  $params['context'] = strtolower($params['context']);
  if (!in_array($params['context'], ['create', 'cancel', 'update'], TRUE)) {
    return RemoteEvent::createStaticAPI3Error(E::ts("Invalid context '%1'", [1 => $params['context']]));
  }

  // FIRSTLY: evaluate TOKEN
  $participant = NULL;
  if (!empty($params['token'])) {
    // identify event via participant
    $usage_map = [
      'create' => ['invite'],
      'update' => ['update', 'invite'],
      'cancel' => ['cancel'],
    ];
    foreach ($usage_map[$params['context']] as $context) {
      if ($participant_id = CRM_Remotetools_SecureToken::decodeEntityToken(
        'Participant',
        trim($params['token']),
        $context
      )) {
        break;
      }
    }
    if (empty($participant_id)) {
      // token is invalid
      if (empty($params['event_id'])) {
        // we can't do anything without event ID
        return RemoteEvent::createStaticAPI3Error(E::ts("Invalid token '%1'", [1 => $params['token']]));
      }
    }
    else {
      // token checks out, get the event_id
      try {
        $participant = civicrm_api3('Participant', 'getsingle', ['id' => $participant_id, 'return' => 'event_id']);
      }
      catch (CRM_Core_Exception $ex) {
        // token is valid, but the participant doesn't exist (any more)
        return RemoteEvent::createStaticAPI3Error(E::ts("Broken token '%1'", [1 => $params['token']]));
      }

      // verify the event_id
      if (isset($params['event_id'])) {
        if ((int) $participant['event_id'] !== (int) $params['event_id']) {
          return RemoteEvent::createStaticAPI3Error(
            E::ts("Token refers to another event '%1'", [1 => $params['token']])
          );
        }
      }
      else {
        $params['event_id'] = $participant['event_id'];
      }
    }
  }

  // SECONDLY: do some sanity checks on the event
  $event_query = civicrm_api3('RemoteEvent', 'get', ['id' => $params['event_id']]);
  if ($event_query['count'] < 1) {
    return RemoteEvent::createStaticAPI3Error(
        E::ts('RemoteEvent [%1] does not exist, or not eligible for registration.', [1 => $params['event_id']])
    );
  }
  elseif ($event_query['count'] > 1) {
    return RemoteEvent::createStaticAPI3Error(
        E::ts('RemoteEvent [%1] is ambiguous.', [1 => $params['event_id']])
    );
  }
  $event = reset($event_query['values']);

  // is remote registration enabled for this event?
  if (empty($event['remote_registration_enabled'])) {
    return RemoteEvent::createStaticAPI3Error(
        E::ts('RemoteEvent [%1] has no remote registration enabled.', [1 => $params['event_id']])
    );
  }

  // then see what action the user wants
  $fields = NULL;
  try {
    switch ($params['context']) {
      case 'create':
        $fields = new GetCreateParticipantFormEvent($params, $event);
        $fields->addStandardFields();
        $fields->addStandardGreeting();
        Civi::dispatcher()->dispatch(GetCreateParticipantFormEvent::NAME, $fields);
        break;

      case 'cancel':
        $fields = new GetCancelParticipantFormEvent($params, $event);
        $fields->addStandardFields();
        $fields->addStandardGreeting();
        Civi::dispatcher()->dispatch(GetCancelParticipantFormEvent::NAME, $fields);
        break;

      case 'update':
        $fields = new GetUpdateParticipantFormEvent($params, $event);
        $fields->addStandardFields();
        $fields->addStandardGreeting();
        Civi::dispatcher()->dispatch(GetUpdateParticipantFormEvent::NAME, $fields);
        break;
    }
  }
  catch (Exception $error) {
    $fields->addError($error->getMessage());
  }

  // return the result
  if ($fields->hasErrors()) {
    return $fields->createAPI3Error();
  }
  else {
    return $fields->createAPI3Success('RemoteEvent', 'get', $fields->getResult());
  }
}
