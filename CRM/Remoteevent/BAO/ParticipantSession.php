<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2023 SYSTOPIA                            |
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

use CRM_Remoteevent_ExtensionUtil as E;

class CRM_Remoteevent_BAO_ParticipantSession extends CRM_Remoteevent_DAO_ParticipantSession
{
    /**
     * Create a new ParticipantSession based on array-data
     *
     * @param array $params key-value pairs
     *
     * @return CRM_Remoteevent_DAO_ParticipantSession|NULL
     */
    public static function create($params)
    {
        $className = 'CRM_Remoteevent_DAO_ParticipantSession';
        $entityName = 'ParticipantSession';
        $hook = empty($params['id']) ? 'create' : 'edit';

        CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
        $instance = new $className();
        $instance->copyValues($params);
        $instance->save();
        CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

        return $instance;
    }

}
