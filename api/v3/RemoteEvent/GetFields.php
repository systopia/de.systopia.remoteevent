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



/*
 * DOESN'T WORK!! 'getfields' is a special (interenal) action that cannot be overwritten this way!
 */



/**
 * RemoteEvent.get implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_remote_event_getfields($params)
{
    // let's start with the basic event fields
    $fields = civicrm_api3('Event', 'getfields', $params);
    // TODO: add RemoteEvent fields
    return $fields;
}
