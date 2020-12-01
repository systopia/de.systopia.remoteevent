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
use \Civi\RemoteParticipant\Event\UpdateEvent as UpdateEvent;

/**
 * Class to execute event registration updates (RemoteParticipant.update)
 */
class CRM_Remoteevent_RegistrationUpdate
{
    const STAGE1_PARTICIPANT_IDENTIFICATION = 5000;
    const STAGE2_APPLY_CONTACT_CHANGES      = -5000;
    const STAGE3_APPLY_PARTICIPANT_CHANGES  = -10000;
    const STAGE4_COMMUNICATION              = -15000;

    const BEFORE_PARTICIPANT_IDENTIFICATION = self::STAGE1_PARTICIPANT_IDENTIFICATION + 50;
    const AFTER_PARTICIPANT_IDENTIFICATION  = self::STAGE1_PARTICIPANT_IDENTIFICATION - 50;
    const BEFORE_APPLY_CONTACT_CHANGES      = self::STAGE2_APPLY_CONTACT_CHANGES + 50;
    const AFTER_APPLY_CONTACT_CHANGES       = self::STAGE2_APPLY_CONTACT_CHANGES - 50;
    const BEFORE_APPLY_PARTICIPANT_CHANGES  = self::STAGE3_APPLY_PARTICIPANT_CHANGES + 50;
    const AFTER_APPLY_PARTICIPANT_CHANGES   = self::STAGE3_APPLY_PARTICIPANT_CHANGES - 50;


    /**
     * Will load the participant data
     *
     * @param UpdateEvent $registration_update
     *   registration update event
     */
    public static function loadParticipant($registration_update)
    {
        $l10n = $registration_update->getLocalisation();

        // of there is already an issue, don't waste any more time on this
        if ($registration_update->hasErrors()) {
            return;
        }

        // load the current participant
        if (empty($registration_update->getParticipant())) {
            $participant_id = $registration_update->getParticipantID();
            if (empty($participant_id)) {
                $registration_update->addError($l10n->localise("Participant could not be identified."));
            } else {
                $registration_update->setParticipant(civicrm_api3('Participant', 'getsingle', ['id' => $participant_id]));
            }
        }

        // load the current contact
        if (empty($registration_update->getContact())) {
            $contact_id = $registration_update->getContactID();
            if (empty($contact_id)) {
                $registration_update->addError($l10n->localise("Contact could not be identified."));
            } else {
                $registration_update->setContact(civicrm_api3('Contact', 'getsingle', ['id' => $contact_id]));
            }
        }
    }

    /**
     * Apply any contact updates
     *
     * @param UpdateEvent $registration_update
     *   registration update event
     */
    public static function updateContact($registration_update)
    {
        // of there is already an issue, don't waste any more time on this
        if ($registration_update->hasErrors()) {
            return;
        }

        // check if there is any updates
        $contact_updates = $registration_update->getContactUpdates();
        if (!empty($contact_updates)) {
            $contact_updates['id'] = $registration_update->getContactID();
            CRM_Remoteevent_CustomData::resolveCustomFields($contact_updates);
            try {
                civicrm_api3('Contact', 'create', $contact_updates);
            } catch (CiviCRM_API3_Exception $ex) {
                $l10n = $registration_update->getLocalisation();
                $registration_update->addError($l10n->localise("Couldn't update contact: %1", [1 => $l10n->localise($ex->getMessage())]));
            }
        }
    }

    /**
     * Apply any participant updates
     *
     * @param UpdateEvent $registration_update
     *   registration update event
     */
    public static function updateParticipant($registration_update)
    {
        // of there is already an issue, don't waste any more time on this
        if ($registration_update->hasErrors()) {
            return;
        }

        // check if there is any updates
        $participant_updates = $registration_update->getParticipantUpdates();
        if (!empty($participant_updates)) {
            $participant_updates['id'] = $registration_update->getParticipantID();
            CRM_Remoteevent_CustomData::resolveCustomFields($participant_updates);
            try {
                civicrm_api3('Participant', 'create', $participant_updates);
            } catch (CiviCRM_API3_Exception $ex) {
                $l10n = $registration_update->getLocalisation();
                $registration_update->addError($l10n->localise("Couldn't update participant: %1", [1 => $l10n->localise($ex->getMessage())]));
            }
        }
    }
}
