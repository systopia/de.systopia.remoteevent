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
    // let's start with the basic event specs
    require_once 'api/v3/Event.php';
    _civicrm_api3_event_get_spec($spec);

    // add extra fields
    $spec['locale'] = [
        'name'         => 'locale',
        'api.required' => 0,
        'title'        => E::ts('Locale'),
        'description'  => E::ts('Locale of the field labels/etc. NOT IMPLEMENTED YET'),
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
    // create an object for the paramters
    $get_params = new GetParamsEvent($params);

    // modify search terms based on user/permission/etc
    $get_params->setParameter('is_template', 0);
    $get_params->setParameter('check_permissions', false);

    if (!CRM_Core_Permission::check('view all Remote Events')) {
        // only basic view permissions -> only list public + active
        $get_params->setParameter('is_public', 0);
        $get_params->setParameter('is_active', false);
    }

    // todo: only view the ones that are open for registration?
    $get_params->setParameter('event_remote_registration.remote_registration_enabled', 1);

    // dispatch search parameters event
    Civi::dispatcher()->dispatch('civi.remoteevent.get.params', $get_params);

    // use the basic event API for queries
    $event_get = $get_params->getParameters();
    CRM_Remoteevent_CustomData::resolveCustomFields($event_get);
    $result     = civicrm_api3('Event', 'get', $event_get);
    $event_list = $result['values'];

    // apply custom field labelling
    foreach ($event_list as $key => &$event) {
        CRM_Remoteevent_CustomData::labelCustomFields($event);
    }

    // strip some private/misleading event data
    $strip_fields = [
        'is_online_registration',
        'event_full_text',
        'is_map',
        'is_show_location',
        'created_id',
        'created_date'
    ];
    foreach ($event_list as $key => &$event) {
        foreach ($strip_fields as $field_name) {
            unset($event[$field_name]);
        }
    }

    // add profile data
    foreach ($event_list as $key => &$event) {
        CRM_Remoteevent_RegistrationProfile::setProfileDataInEventData($event);
    }

    // dispatch the event in case somebody else wants to add something
    $result = new GetResultEvent($event_get, $event_list);
    Civi::dispatcher()->dispatch('civi.remoteevent.get.result', $result);

    // return the result
    return civicrm_api3_create_success($result->getEventData());
}
