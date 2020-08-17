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

}
