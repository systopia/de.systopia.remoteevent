<?php
/*
 * Copyright (C) 2024 SYSTOPIA GmbH
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

use Civi\Api4\OptionValue;

final class CRM_Remoteevent_RegistrationProfile_Mock extends \CRM_Remoteevent_RegistrationProfile {

  public const NAME = 'Mock';

  /**
   * The fields returned by getFields().
   *
   * @phpstan-var array<string, array<string, mixed>>
   *
   * @see \CRM_Remoteevent_RegistrationProfile::getFields()
   */
  public static array $fields = [];

  public static function register(): void {
    OptionValue::create(FALSE)
      ->setValues([
        'option_group_id.name' => 'remote_registration_profiles',
        'name' => self::NAME,
        'label' => 'Mock Profile',
        'is_active' => TRUE,
      ])->execute();
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return self::NAME;
  }

  /**
   * @inheritDoc
   *
   * @phpstan-return array<string, array<string, mixed>>
   */
  public function getFields($locale = NULL): array {
    return self::$fields;
  }

}
