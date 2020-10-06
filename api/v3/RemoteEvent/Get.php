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
    // add extra fields
    $spec['locale'] = [
        'name'         => 'locale',
        'api.required' => 0,
        'title'        => E::ts('Locale'),
        'description'  => E::ts('Locale of the field labels/etc. NOT IMPLEMENTED YET'),
    ];

    $spec['remote_contact_id'] = [
        'name'         => 'remote_contact_id',
        'api.required' => 0,
        'title'        => E::ts('Remote Contact ID'),
        'description'  => E::ts(
            'You can submit a remote contact, in which case the result will be filtered for the events available to that contact'
        ),
    ];
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

    // create an object for the paramters
    $get_params = new GetParamsEvent($params);

    // modify search terms based on user/permission/etc
    $get_params->setParameter('is_template', 0);
    $get_params->setParameter('check_permissions', false);

    if (!CRM_Core_Permission::check('view all Remote Events')) {
        // only basic view permissions -> only list public + active
        $get_params->setParameter('is_public', 0);
        $get_params->setParameter('is_active', 0);
    }

    // todo: only view the ones that are open for registration?
    $get_params->setParameter('event_remote_registration.remote_registration_enabled', 1);

    // dispatch search parameters event
    Civi::dispatcher()->dispatch('civi.remoteevent.get.params', $get_params);

    // use the basic event API for queries
    $event_get = $get_params->getParameters();
    CRM_Remoteevent_CustomData::resolveCustomFields($event_get);
    unset($event_get['return']); // we need the full event to cache
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

    // create the event
    $result = new GetResultEvent($event_get, $event_list);

    // add flags (will be overwritten by event handlers)
    $remote_contact_id = $get_params->getRemoteContactID();
    foreach ($result->getEventData() as &$event) {
        $cant_register_reason = CRM_Remoteevent_Registration::cannotRegister($event['id'], $remote_contact_id, $event);
        if ($cant_register_reason) {
            $event['can_register'] = 0;
            $result->logMessage($cant_register_reason);
        } else {
            $event['can_register'] = 1;
        }
        // can_instant_register only if can_register
        $event['can_instant_register'] = (int)
            ($event['can_register']
                && CRM_Remoteevent_Registration::canOneClickRegister($event['id'], $event));
    }

    // add personal flags
    if ($remote_contact_id) {
        CRM_Remoteevent_Registration::cacheRegistrationData($event_ids, $remote_contact_id);
        foreach ($result->getEventData() as &$event) {
            $event['participant_registration_count'] = (int)
                CRM_Remoteevent_Registration::getRegistrationCount($event['id'], $remote_contact_id, ['Positive', 'Pending']);
            $event['can_edit_registration'] =
                (int)(
                    $event['participant_registration_count'] > 0
                        && CRM_Remotetools_ContactRoles::hasRole($remote_contact_id, 'remote-event-user')
                );
            $event['is_registered'] = $event['participant_registration_count'] > 0 ? 1 : 0;
        }
    }

    // add other info
    foreach ($result->getEventData() as &$event) {
        $event['registration_count'] =
            count(CRM_Remoteevent_Registration::getRegistrations($event['id']));
    }

    // dispatch the event in case somebody else wants to add something
    Civi::dispatcher()->dispatch('civi.remoteevent.get.result', $result);

    // finally: filter for flags
    $event_list = &$result->getEventData();
    $event_ids = null; // this might not be valid after this section
    $query_values = $result->getQueryParameters();
    foreach (['can_register', 'can_instant_register', 'is_registered', 'can_edit_registration'] as $flag) {
        if (isset($query_values[$flag])) {
            $queried_value = empty($query_values[$flag]) ? 0 : 1;
            foreach (array_keys($event_list) as $event_key) {
                $event = $event_list[$event_key];
                $event_value = (int) CRM_Utils_Array::value($flag, $event, -1);
                if ($event_value != $queried_value) {
                    // filter this event
                    unset($event_list[$event_key]);
                }
            }
        }
    }

    // return the result
    return civicrm_api3_create_success($result->getEventData());
}
