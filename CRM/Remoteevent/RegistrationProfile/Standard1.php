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
class CRM_Remoteevent_RegistrationProfile_Standard1 extends CRM_Remoteevent_RegistrationProfile
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
     * @param string $locale
     *   the locale to use, defaults to null (current locale)
     *
     * @return array field specs
     * @see CRM_Remoteevent_RegistrationProfile::getFields()
     *
     */
    public function getFields($locale = null)
    {
        $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
        return [
            'email' => [
                'name'        => 'email',
                'type'        => 'Text',
                'validation'  => 'Email',
                'weight'      => 10,
                'required'    => 1,
                'label'       => $l10n->localise('Email'),
                'description' => $l10n->localise("Participant's email address"),
                'group_name'  => 'contact_base',
                'group_label' => $l10n->localise("Contact Data"),
            ]
        ];
    }
}
