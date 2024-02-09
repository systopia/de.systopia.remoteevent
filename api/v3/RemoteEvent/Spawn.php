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
use \Civi\RemoteEvent\Event\SpawnParamsEvent;

/**
 * RemoteEvent.create specification
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_event_spawn_spec(&$spec)
{
    $spec['template_id'] = [
        'name'         => 'template_id',
        'api.required' => 0,
        'type'         => CRM_Utils_Type::T_INT,
        'title'        => E::ts('Template ID'),
        'description'  => E::ts('If the ID of an existing event or event template is given, the new event will be based on that.'),
    ];
    $spec['event_type_id'] = [
        'name'         => 'event_type_id',
        'api.required' => 0,
        'type'         => CRM_Utils_Type::T_INT,
        'title'        => E::ts('Event Type'),
        'description'  => E::ts('Type of the event'),
    ];
    $spec['title'] = [
        'name'         => 'title',
        'api.required' => 1,
        'type'         => CRM_Utils_Type::T_STRING,
        'title'        => E::ts('Event Title'),
        'description'  => E::ts('Title of the event'),
    ];
    $spec['start_date'] = [
        'name'         => 'start_date',
        'api.required' => 1,
        'type'         => CRM_Utils_Type::T_STRING,
        'title'        => E::ts('Event Start + Time'),
    ];
    $spec['end_date'] = [
        'name'         => 'end_date',
        'api.required' => 0,
        'type'         => CRM_Utils_Type::T_STRING,
        'title'        => E::ts('Event End + Time'),
    ];
}

/**
 * RemoteEvent.spawn implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_remote_event_spawn($params)
{
    unset($params['check_permissions']);

    // create an object for the parameters
    $create_params = new SpawnParamsEvent($params);

    // dispatch search parameters event and get parameters
    Civi::dispatcher()->dispatch(SpawnParamsEvent::NAME, $create_params);
    $event_create = $create_params->getParameters();

    // if there is a template_id id given, we want to clone that first
    if (!empty($event_create['template_id'])) {
        $template_id = (int) $event_create['template_id'];
        if (!$template_id) {
          throw new Exception("Invalid template ID");
        }

        // use APIv4 to handle this
        // @see https://github.com/systopia/de.systopia.remoteevent/issues/8
        $event_data = civicrm_api4('Event', 'get', [
          'select' => ['*', 'custom.*'],
          'where' => [['id', '=', $template_id]],
          'limit' => 1,
          'checkPermissions' => false,
        ])->getArrayCopy()[0];

        // remove ids and merge additional data
        $event_create = array_merge($event_data, $event_create);

        // remove template data and api artifacts
        unset($event_create['id'], $event_create['template_id'], $event_create['created_id'], $event_create['created_date'], $event_create['version'], $event_create['prettyprint']);
        if (empty($event_create['start_date'])) $event_create['start_date'] = date('YmdHis');
        $event_create['title'] = $event_data['title'] ?? $event_create['template_title'] ?? E::ts("New Event");
        $event_create['is_template'] = 0;
        $event_create['template_title'] = '';


        $create_call = \Civi\Api4\Event::create(false);
        foreach ($event_create as $name => $value) {
          $create_call->addValue($name, $value);
        }
        $result = $create_call->execute();
        $new_event = $result->first();

        // copy sessions:
        CRM_Remoteevent_BAO_Session::copySessions($template_id, $new_event['id']);

    } else {
        // this is the scenario where no 'template_id' is given, so we'll just run a create
        CRM_Remoteevent_CustomData::resolveCustomFields($event_create);
        Civi::log()->debug("Event.create(via spawn) parameters: " . json_encode($event_create));
        $new_event = civicrm_api3('Event', 'create', $event_create);
    }

    $null = null;
    return civicrm_api3_create_success([], $params, 'RemoteEvent', 'spawn', $null, ['id' => $new_event['id']]);
}
