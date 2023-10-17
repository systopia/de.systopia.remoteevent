<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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

namespace Civi\RemoteEvent\Event;
use Civi\RemoteParamsEvent;

/**
 * Class CreateParamsEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteEvent.create API call, so the search parameters can be manipulated
 */
class SpawnParamsEvent extends RemoteParamsEvent
{
    const NAME = 'civi.remoteevent.spawn.params';
}
