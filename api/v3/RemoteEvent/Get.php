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

    // todo: modify fields
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
    // todo: modify search terms based on user/permission/etc

    // use the basic event API for queries
    $result = civicrm_api3('Event', 'get', $params);
    $event_list = $result['values'];

    // todo: modify event data

    return civicrm_api3_create_success($event_list);
}
