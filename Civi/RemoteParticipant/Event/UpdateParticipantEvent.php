<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2023 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


namespace Civi\RemoteParticipant\Event;

use CRM_Remoteevent_ExtensionUtil as E;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event class to modify participant data.
 * get_participant_data returns current data, which can be overwritten with set_participan_data afterwards
 * After the event is run the (modified) participant data can be used
 */
class UpdateParticipantEvent extends Event
{
    public const NAME = 'civi.remoteparticipant.update.participant';

    private $participant_data;

    public function __construct($participant_data) {
        $this->participant_data = $participant_data;
    }

    public function get_participant_data() {
        return $this->participant_data;
    }

    public function set_participant_data($data) {
        $this->participant_data = $data;
    }
}