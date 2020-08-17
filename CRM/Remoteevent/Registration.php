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
use \Civi\RemoteParticipant\Event\RegistrationEvent as RegistrationEvent;

/**
 * Class to coordinate event registrations (RemoteParticipant)
 */
class CRM_Remoteevent_Registration
{
    /**
     * @param integer $event_id
     *      the event you want to register to
     *
     * @param integer|null $contact_id
     *      the contact trying to register (in case of restricted registration)
     *
     * @return boolean
     *      is the registration allowed
     */
    public static function canRegister($event_id, $contact_id = null) {
        // todo: check event status, availability, date, etc.
        return true;
    }

    /**
     * Will identify a contact by its remote ID
     *
     * @param RegistrationEvent $registration
     *   registration event
     */
    public static function identifyRemoteContact($registration)
    {
        if (!$registration->getContactID() && !empty($registration->getSubmittedValue('remote_contact_id'))) {
            $contact_id = CRM_Remotetools_Contact::getByKey($registration->getSubmittedValue('remote_contact_id'));
            if ($contact_id) {
                $registration->setContactID($contact_id);
            }
        }
    }

    /**
     * Will create a simple participant object
     *
     * @param RegistrationEvent $registration
     *   registration event
     */
    public static function createParticipant($registration)
    {
        if ($registration->getParticipantID()) {
            // someone else has done it
            return;
        }

        // create a simple participant
        $creation = civicrm_api3('Participant', 'create', [
            'contact_id' => $registration->getContactID(),
            'event_id'   => $registration->getEventID()
        ]);
        $participant = civicrm_api3('Participant', 'getsingle', ['id' => $creation['id']]);
        $registration->setParticipant($participant);
    }

}
