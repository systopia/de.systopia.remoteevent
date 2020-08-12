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
 * Implements profile 'Standard3': Email, prefix, title, first name, last name, postal address and phone",
 */
class CRM_Remoteevent_RegistrationProfile_Standard3 extends  CRM_Remoteevent_RegistrationProfile_Standard2
{
    /**
     * Get the internal name of the profile represented
     *
     * @return string name
     */
    public function getName()
    {
        return 'Standard3';
    }

    /**
     * @see CRM_Remoteevent_RegistrationProfile::getFields()
     *
     * @param string $locale
     *   the locale to use, defaults to null (current locale)
     *
     * @return array field specs
     */
    public function getFields($locale = null) {
        $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
        return array_merge(parent::getFields($locale), [
            'phone' => [
                'name'        => 'phone',
                'type'        => 'Text',
                'validation'  => '',
                'weight'      => 100,
                'required'    => 0,
                'label'       => $l10n->localise('Phone Number'),
                'description' => $l10n->localise("Participant's Phone Number"),
            ],
            // TODO: add postal address
        ]);
    }
}
