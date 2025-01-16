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

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\RemoteEvent\Api4\Action\RemoteEventMailingList\ConfirmSubscriptionAction;

final class RemoteEventMailingList extends AbstractEntity {

  public static function confirmSubscription(): ConfirmSubscriptionAction {
    return new ConfirmSubscriptionAction();
  }

  /**
   * @inheritDoc
   */
  public static function getFields() {
    return new BasicGetFieldsAction(self::getEntityName(), __FUNCTION__, fn () => []);
  }

  public static function permissions(): array {
      return [
        'meta' => ['register to Remote Events'],
        'default' => ['register to Remote Events'],
      ];
    }

}
