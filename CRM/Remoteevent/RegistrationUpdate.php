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
use \Civi\RemoteParticipant\Event\GetCreateParticipantFormEvent as GetCreateParticipantFormEvent;

/**
 * Class to execute event registration updates (RemoteParticipant.update)
 */
class CRM_Remoteevent_RegistrationUpdate
{
    const STAGE1_PARTICIPANT_IDENTIFICATION = 5000;
    const STAGE2_APPLY_CHANGES              = -5000;
    const STAGE3_COMMUNICATION              = -10000;

    const BEFORE_PARTICIPANT_IDENTIFICATION = self::STAGE1_PARTICIPANT_IDENTIFICATION + 50;
    const AFTER_PARTICIPANT_IDENTIFICATION  = self::STAGE1_PARTICIPANT_IDENTIFICATION - 50;
    const BEFORE_APPLY_CHANGES              = self::STAGE2_APPLY_CHANGES + 50;
    const AFTER_APPLY_CHANGES               = self::STAGE2_APPLY_CHANGES - 50;

}
