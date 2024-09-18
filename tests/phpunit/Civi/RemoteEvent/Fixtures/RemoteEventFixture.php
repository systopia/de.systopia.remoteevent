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

namespace Civi\RemoteEvent\Fixtures;

use Civi\Api4\Event;

final class RemoteEventFixture {

  private static int $count = 0;

  /**
   * @phpstan-param array<string, mixed> $values
   *
   * @phpstan-return array<string, mixed>
   *
   * @throws \CRM_Core_Exception
   */
  public static function addFixture(array $values = []): array {
    $values += [
      'title' => 'Event ' . ++self::$count,
      'event_type_id' => 1,
      'start_date' => date('Y-m-d', strtotime('tomorrow')),
      'default_role_id' => 1,
      'is_active' => TRUE,
      'event_remote_registration.remote_registration_enabled' => TRUE,
      'event_remote_registration.remote_disable_civicrm_registration' => TRUE,
      'event_remote_registration.remote_registration_default_profile' => \CRM_Remoteevent_RegistrationProfile_Mock::NAME,
      'event_remote_registration.remote_registration_profiles' => [\CRM_Remoteevent_RegistrationProfile_Mock::NAME],
      'event_remote_registration.remote_registration_default_update_profile' => \CRM_Remoteevent_RegistrationProfile_Mock::NAME,
      'event_remote_registration.remote_registration_update_profiles' => [\CRM_Remoteevent_RegistrationProfile_Mock::NAME],
    ];

    // Ensure default profiles are enabled
    $defaultProfile = $values['event_remote_registration.remote_registration_default_profile'];
    if (!in_array($defaultProfile, $values['event_remote_registration.remote_registration_profiles'], TRUE)) {
      $values['event_remote_registration.remote_registration_profiles'][] = $defaultProfile;
    }
    $defaultUpdateProfile = $values['event_remote_registration.remote_registration_default_update_profile'];
    if (!in_array($defaultUpdateProfile, $values['event_remote_registration.remote_registration_update_profiles'], TRUE)) {
      $values['event_remote_registration.remote_registration_update_profiles'][] = $defaultUpdateProfile;
    }

    return Event::create(FALSE)
      ->setValues($values)
      ->execute()
      ->single();
  }

}
