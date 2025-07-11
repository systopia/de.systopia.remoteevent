<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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

use Civi\Api4\PriceFieldValue;
use CRM_Remoteevent_Localisation;

class PriceFieldUtil {

  /**
   * @phpstan-var array<int, array<string, mixed>>
   */
  public static array $priceFieldValues = [];

  /**
   * @phpstan-param array{id: int} $event
   *
   * @phpstan-return array<int, array<string, mixed>>
   */
  public static function getPriceFields(array $event): array {
    return \Civi\Api4\Event::get(FALSE)
      ->addSelect(
        'price_field.*',
        'price_field_value.id',
        'price_field_value.max_value',
      )
      ->addJoin(
        'PriceSetEntity AS price_set_entity',
        'INNER',
        ['price_set_entity.entity_table', '=', '"civicrm_event"'],
        ['price_set_entity.entity_id', '=', 'id']
      )
      ->addJoin(
        'PriceSet AS price_set',
        'INNER',
        ['price_set.id', '=', 'price_set_entity.price_set_id'],
        ['price_set.is_active', '=', 1]
      )
      ->addJoin(
        'PriceField AS price_field',
        'LEFT',
        ['price_field.price_set_id', '=', 'price_set.id']
      )
      // For price fields with a selectable quantity, there is one single price field value; include its ID.
      ->addJoin(
        'PriceFieldValue AS price_field_value',
        'LEFT',
        ['price_field_value.price_field_id', '=', 'price_field.id'],
        ['price_field.is_enter_qty', '=', TRUE]
      )
      ->addWhere('id', '=', $event['id'])
      ->execute()
      ->indexBy('price_field.id')
      ->getArrayCopy();
  }

  /**
   * @phpstan-return array<int, array<string, mixed>>
   */
  public static function getPriceFieldValues(int $priceFieldId): array {
    return self::$priceFieldValues[$priceFieldId] ??= PriceFieldValue::get(FALSE)
      ->addWhere('price_field_id', '=', $priceFieldId)
      ->execute()
      ->indexBy('id')
      ->getArrayCopy();
  }

}
