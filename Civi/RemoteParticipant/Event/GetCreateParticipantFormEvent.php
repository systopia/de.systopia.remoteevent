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


namespace Civi\RemoteParticipant\Event;
use Civi\RemoteEvent;

/**
 * Class GetCreateParticipantFormEvent
 *
 * This event will be triggered to define the form of a new registration via
 *   RemoteParticipant.get_form API with action=create
 */
class GetCreateParticipantFormEvent extends GetParticipantFormEventBase
{
    /**
     * Get the token usage key for this event type
     *
     * @return string
     */
    protected function getTokenUsage()
    {
        return 'invite';
    }
}
