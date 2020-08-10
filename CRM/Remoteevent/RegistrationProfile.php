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

use CRM_Remoteevent_ExtensionUtil as E;


/**
 * UI changes to CiviCRM's generic event UI
 */
class CRM_Remoteevent_RegistrationProfile
{
    /**
     * Get a list of all currently available registration profiles
     *
     * @todo cache?
     */
    public static function getAvailableRegistrationProfiles()
    {
        $profiles = [];
        $result = civicrm_api3('OptionValue', 'get', [
            'option.limit'    => 0,
            'option_group_id' => 'remote_registration_profiles',
            'is_active'       => 1
        ]);
        foreach ($result['values'] as $profile) {
            $profiles[$profile['value']] = $profile['label'];
        }
        return $profiles;
    }
}
