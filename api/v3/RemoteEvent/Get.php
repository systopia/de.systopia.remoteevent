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

use CRM_Remoteevent_ExtensionUtil as E;
use \Civi\RemoteEvent\Event\GetParamsEvent as GetParamsEvent;
use \Civi\RemoteEvent\Event\GetResultEvent as GetResultEvent;

/**
 * RemoteEvent.get specification
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_event_get_spec(&$spec)
{
    // not in use, see civicrm_api3_remote_event_getfields_get
}

/**
 * RemoteEvent.get implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_remote_event_get($params)
{
    unset($params['check_permissions']);

    // create an object for the parameters
    $get_params = new GetParamsEvent($params);

    // modify search terms based on user/permission/etc
    $get_params->setParameter('is_template', 0);
    $get_params->setParameter('check_permissions', false);

    if (!CRM_Core_Permission::check('view all Remote Events')) {
        // only basic view permissions -> only list public + active
        $get_params->setParameter('is_public', 1);
        $get_params->setParameter('is_active', 1);
    }

    // todo: only view the ones that are open for registration?
    $get_params->setParameter('event_remote_registration.remote_registration_enabled', 1);

    // Translate event ID.
    $original_params = $get_params->getOriginalParameters();
    if (!empty($original_params['id'])) {
      $get_params->setParameter('event_id', $original_params['id']);
    }

    // dispatch search parameters event
    Civi::dispatcher()->dispatch(GetParamsEvent::NAME, $get_params);

    // use the basic event API for queries
    $event_get = $get_params->getParameters();
    CRM_Remoteevent_CustomData::resolveCustomFields($event_get);
    unset($event_get['return']); // we need the full event to cache
    Civi::log()->debug("RemoteEvent generated Event.get: " . json_encode($event_get));
    $result     = civicrm_api3('Event', 'get', $event_get);
    $event_list = $result['values'];
    $event_ids  = [];

    // apply custom field labelling
    foreach ($event_list as $key => &$event) {
        CRM_Remoteevent_CustomData::labelCustomFields($event);
        $event_ids[] = (int) $event['id'];
        CRM_Remoteevent_EventCache::cacheEvent($event);
    }

    // strip some private/misleading event data
    foreach ($event_list as $key => &$event) {
        foreach (CRM_Remoteevent_RemoteEvent::STRIP_FIELDS as $field_name) {
            unset($event[$field_name]);
        }
    }

    // add profile data
    foreach ($event_list as $key => &$event) {
        CRM_Remoteevent_RegistrationProfile::setProfileDataInEventData($event);
    }

    // check if this is a personalised request
    $contact_id = $get_params->getContactID();
    if ($contact_id) {
        CRM_Remoteevent_Registration::cacheRegistrationData($event_ids, $contact_id);
    }

    //         NEXT STEP: CUSTOMISING

    // create a symfony event instance for further processing
    $result = new GetResultEvent($event_get, $event_list, $get_params->getOriginalParameters());

    // add some basic information
    foreach ($result->getEventData() as &$event) { // todo: optimise queries (over all events)?
        // add counts and other data
        $event['event_type'] =
            CRM_Remoteevent_RemoteEvent::getEventType($event, $result->getLocalisation());
        $event['registration_count'] =
            CRM_Remoteevent_Registration::getRegistrationCount($event['id']);
    }


    // dispatch the event in case somebody else wants to add/remove something
    Civi::dispatcher()->dispatch(GetResultEvent::NAME, $result);

    // finally, apply the limit
    if ($get_params->getLimit() != $get_params->getOriginalLimit()) {
        $result->trimToLimit($get_params->getOriginalLimit(), $get_params->getOriginalOffset());
    }

    // return the result
    if ($result->hasErrors()) {
        return $result->createAPI3Error();
    } else {
        return $result->createAPI3Success('RemoteEvent', 'get', $result->getFinalEventData());
    }
}
