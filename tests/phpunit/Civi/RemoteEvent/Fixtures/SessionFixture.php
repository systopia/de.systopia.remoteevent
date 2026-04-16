<?php
/*
 * Copyright (C) 2026 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\RemoteEvent\Fixtures;

use Civi\Api4\Session;

final class SessionFixture {

  /**
   * @param int $eventId
   * @param array<string, mixed> $values
   *
   * @return array{id: int, ...}
   */
  public static function addFixture(int $eventId, array $values = []): array {
    $values += ['event_id' => $eventId];

    // @phpstan-ignore return.type
    return Session::create(FALSE)
      ->setValues($values)
      ->execute()
      ->single();
  }

}
