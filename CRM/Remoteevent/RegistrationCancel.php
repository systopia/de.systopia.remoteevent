<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviRemote Event Extension                    |
| Copyright (C) 2023 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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
use Civi\RemoteParticipant\Event\CancelEvent;
use Civi\Api4\Participant;

/**
 * Class to execute event registration cancellations (RemoteParticipant.cancel).
 */
class CRM_Remoteevent_RegistrationCancel {

  /**
   * @param \Civi\RemoteParticipant\Event\CancelEvent $event
   *
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function cancelAdditionalParticipants(CancelEvent $event) {
    if (!empty($cancellations = $event->getParticipantCancellations())) {
      $additional_participants = Participant::get(FALSE)
        ->addWhere(
          'registered_by_id',
          'IN',
          array_column($cancellations, 'id')
        )
        ->execute();
      foreach ($additional_participants as $additional_participant) {
        if ($event->participantCanBeCancelled($additional_participant)) {
          $event->addCancellation((int) $additional_participant['id']);
        }
      }
    }
  }

}
