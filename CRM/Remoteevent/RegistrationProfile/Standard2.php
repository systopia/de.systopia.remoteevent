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
use \Civi\RemoteEvent\Event\GetRegistrationFormResultsEvent as GetRegistrationFormResultsEvent;


/**
 * Implements profile 'Standard2': Email, prefix, title, first and last name
 */
class CRM_Remoteevent_RegistrationProfile_Standard2 extends CRM_Remoteevent_RegistrationProfile
{
    /**
     * Get the internal name of the profile represented
     *
     * @return string name
     */
    public function getName()
    {
        return 'Standard2';
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
            'email'        => [
                'name'        => 'email',
                'type'        => 'Text',
                'validation'  => 'Email',
                'weight'      => 10,
                'required'    => 1,
                'label'       => $l10n->localise('Email'),
                'description' => $l10n->localise("Participant's email address"),
                'group_name'  => 'contact_base',
                'group_label' => $l10n->localise("Contact Data"),
            ],
            'prefix_id'    => [
                'name'        => 'prefix_id',
                'type'        => 'Select',
                'validation'  => '',
                'weight'      => 20,
                'required'    => 1,
                'options'     => $this->getOptions('individual_prefix', $locale),
                'label'       => $l10n->localise('Prefix'),
                'description' => $l10n->localise("Participant's Prefix"),
                'group_name'  => 'contact_base',
                'group_label' => $l10n->localise("Contact Data"),
            ],
            'formal_title' => [
                'name'        => 'formal_title',
                'type'        => 'Text',
                'validation'  => '',
                'weight'      => 30,
                'required'    => 0,
                'label'       => $l10n->localise('Title'),
                'description' => $l10n->localise("Participant's Formal Title"),
                'group_name'  => 'contact_base',
                'group_label' => $l10n->localise("Contact Data"),
            ],
            'first_name'   => [
                'name'        => 'first_name',
                'type'        => 'Text',
                'validation'  => '',
                'weight'      => 40,
                'required'    => 1,
                'label'       => $l10n->localise('First Name'),
                'description' => $l10n->localise("Participant's First Name"),
                'group_name'  => 'contact_base',
                'group_label' => $l10n->localise("Contact Data"),
            ],
            'last_name'    => [
                'name'        => 'last_name',
                'type'        => 'Text',
                'validation'  => '',
                'weight'      => 50,
                'required'    => 1,
                'label'       => $l10n->localise('Last Name'),
                'description' => $l10n->localise("Participant's Last Name"),
                'group_name'  => 'contact_base',
                'group_label' => $l10n->localise("Contact Data"),
            ],
        ];
    }

    /**
     * Add the default values to the form data, so people using this profile
     *  don't have to enter everything themselves
     *
     * @param GetRegistrationFormResultsEvent $resultsEvent
     *   the locale to use, defaults to null none. Use 'default' for current
     *
     */
    public function addDefaultValues(GetRegistrationFormResultsEvent $resultsEvent)
    {
        $this->addDefaultContactValues($resultsEvent, ['prefix_id', 'email', 'formal_title', 'first_name', 'last_name']);
    }
}
