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

require_once 'remoteevent.civix.php';

use \Civi\RemoteParticipant\Event\ValidateEvent as ValidateEvent;
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * RemoteParticipant.validate specification
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_participant_validate_spec(&$spec)
{
    $spec['event_id']          = [
        'name'         => 'event_id',
        'api.required' => 1,
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
        'description'  => E::ts('You can submit a remote contact ID, to determine the CiviCRM contact'),
    ];
    $spec['locale']            = [
        'name'         => 'locale',
        'api.required' => 0,
        'title'        => E::ts('Locale'),
        'description'  => E::ts('Locale of the field labels/etc. NOT IMPLEMENTED YET'),
    ];
}

/**
 * RemoteParticipant.submit: Will validate the submission/registration data
 *   and return a list of errors for the given fields
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 *
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_remote_participant_validate($params)
{
    $validation = new ValidateEvent($params);

    // identify a given contact ID
    $contact_id = null;
    if (!empty($params['remote_contact_id'])) {
        $contact_id = CRM_Remotetools_Contact::getByKey($params['remote_contact_id']);
        if (!$contact_id) {
            $validation->addError('remote_contact_id', E::ts("RemoteContactID is invalid"));
        }
    }

    // first: check if registration is enabled
    $is_enabled = CRM_Remoteevent_Registration::canRegister($params['event_id'], $contact_id);
    if (!$is_enabled) {
        $validation->addError('event_id', E::ts("RemoteEvent [%1] does not exist does not accept registrations.", [1 => $params['event_id']]));
    } else {
        // dispatch the validation event for other validations to weigh in
        Civi::dispatcher()->dispatch('civi.remoteevent.registration.validate', $validation);
    }

    if ($validation->hasErrors()) {
        $reply = civicrm_api3_create_success($validation->getErrors());
        // todo: how to return a validation fail? error?
        $reply['is_error'] = 1;
        $reply['error_msg'] = E::ts("Registration data incomplete or invalid");
        return $reply;
    } else {
        return civicrm_api3_create_success([]);
    }
}