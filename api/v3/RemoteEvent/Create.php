<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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
use \Civi\RemoteEvent\Event\CreateParamsEvent as CreateParamsEvent;

/**
 * RemoteEvent.create specification
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_event_create_spec(&$spec)
{
    _civicrm_api3_event_create_spec($spec);
    $spec['template_id'] = [
        'name'         => 'template_id',
        'api.required' => 0,
        'title'        => E::ts('Template ID'),
        'description'  => E::ts('If the ID of an existing event or event template is given, the new event will be based on that.'),
    ];

    // disallow update for the moment
    unset($spec['id']);

}

/**
 * RemoteEvent.create implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_remote_event_create($params)
{
    unset($params['check_permissions']);

    // create an object for the parameters
    $create_params = new CreateParamsEvent($params);

    // dispatch search parameters event and get parameters
    Civi::dispatcher()->dispatch('civi.remoteevent.create.params', $create_params);
    $event_create = $create_params->getParameters();

    // if there is a template_id id given, we want to clone that first
    if (!empty($event_create['template_id'])) {
        // todo: error handling
        $cloned_event = CRM_Event_BAO_Event::copy($event_create['template_id']);
        unset($event_create['template_id']);
        $event_create['id'] = $cloned_event->id;
    }

    // use the basic event API for the application of the requested data
    CRM_Remoteevent_CustomData::resolveCustomFields($event_create);
    $result = civicrm_api3('Event', 'create', $event_create);

    // todo: error handling / filtering
    return civicrm_api3('RemoteEvent', 'getsingle', ['id' => $result['id']]);
}
