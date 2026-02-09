<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2023 SYSTOPIA                            |
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

declare(strict_types = 1);

namespace Civi\Api4;

/**
 * Session entity.
 *
 * @package Civi\Api4
 */
class ParticipantSession extends Generic\DAOEntity {

  public static function permissions() {
    return [
      'meta' => ['access CiviEvent'],
      'default' => ['access CiviEvent'],
      'create' => ['access CiviEvent', 'edit all events'],
    ];
  }

}
