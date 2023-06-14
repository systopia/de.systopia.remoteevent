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
use \Civi\RemoteParticipant\Event\ValidateEvent as ValidateEvent;
use Civi\RemoteParticipant\Event\GetParticipantFormEventBase as GetParticipantFormEventBase;

/**
 * Implements profile 'Standard1': Email only
 */
class CRM_Remoteevent_RegistrationProfile_OneClick extends CRM_Remoteevent_RegistrationProfile
{
    /**
     * Get the internal name of the profile represented
     *
     * @return string name
     */
    public function getName()
    {
        return 'OneClick';
    }

    /**
     * @return mixed|string
     */
    public function getLabel()
    {
        $result = civicrm_api3('OptionValue', 'getsingle', [
            'option_group_id' => "remote_registration_profiles",
            'name' => "OneClick",
        ]);
        return $result['label'];
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
        return [];
    }

    /**
     * Add the default values to the form data, so people using this profile
     *  don't have to enter everything themselves
     *
     * @param GetParticipantFormEventBase $resultsEvent
     *   the locale to use, defaults to null none. Use 'default' for current
     *
     */
    public function addDefaultValues(GetParticipantFormEventBase $resultsEvent, $name = NULL)
    {
        // nothing to do here
    }

    /**
     * Validate the profile fields individually.
     * This only validates the mere data types,
     *   more complex validation (e.g. over multiple fields)
     *   have to be performed by the profile implementations
     *
     * @param ValidateEvent $validationEvent
     *      event triggered by the RemoteParticipant.validate or submit API call
     */
    public function validateSubmission($validationEvent)
    {
        parent::validateSubmission($validationEvent);

        // just make sure we have the contact
        if (!$validationEvent->getContactID() && !$validationEvent->getParticipantID()) {
            $l10n = $validationEvent->getLocalisation();
            $validationEvent->addError($l10n->localise("User must be identified to use one-click registration"));
        }
    }

}
