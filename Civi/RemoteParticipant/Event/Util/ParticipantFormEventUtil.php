<?php
/*
 * Copyright (C) 2023 SYSTOPIA GmbH
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

namespace Civi\RemoteParticipant\Event\Util;

use Civi\RemoteParticipant\Event\GetParticipantFormEventBase;

final class ParticipantFormEventUtil {

  /**
   * @phpstan-param array<string, mixed> $values
   * @phpstan-param array<string, string> $mapping
   *   Mapping of entity field names to profile field keys.
   * @phpstan-param array<string, (callable(mixed, array<string, mixed): mixed)> $valueCallbacks
   *
   * @internal
   */
  public static function mapToPrefill(array $values, array $mapping, GetParticipantFormEventBase $event, array $valueCallbacks): void {
    foreach ($mapping as $entity_field_name => $field_key) {
      if (array_key_exists($entity_field_name, $values)) {
        $value = isset($valueCallbacks[$field_key])
                  ? $valueCallbacks[$field_key]($values[$entity_field_name], $values)
                  : $values[$entity_field_name];
        $event->setPrefillValue($field_key, $value);
      }
    }
  }

}
