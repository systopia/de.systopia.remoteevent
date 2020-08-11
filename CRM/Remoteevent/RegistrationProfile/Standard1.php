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
 * Implements profile 'Standard1': Email only
 */
class CRM_Remoteevent_RegistrationProfile_Default1 extends  CRM_Remoteevent_RegistrationProfile
{
    /**
     * Get the internal name of the profile represented
     *
     * @return string name
     */
    public function getName()
    {
        return 'Standard1';
    }

    /**
     * @see CRM_Remoteevent_RegistrationProfile::getFields()
     *
     * @return array
     */
    public function getFields() {
        return [
            'email' => [
                'type'       => 'string',
                'validation' => 'Email'
            ]
        ];
    }
}
