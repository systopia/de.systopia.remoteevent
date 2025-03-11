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

namespace Civi\RemoteEvent\Api4\Action\RemoteEventMailingList;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\RemoteEventMailingList;
use Civi\RemoteEvent\Exception\InvalidSubscriptionTokenException;
use Civi\RemoteParticipant\MailingList\MailingListSubscriptionManager;
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * @method string getToken()
 * @method $this setToken(string $token)
 */
final class ConfirmSubscriptionAction extends AbstractAction {

  /**
   * @var string
   * @required
   */
  protected ?string $token = NULL;

  public function __construct() {
    parent::__construct(RemoteEventMailingList::getEntityName(), 'confirmSubscription');
  }

  /**
   * @inheritDoc
   *
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result): void {
    /** @var \Civi\RemoteParticipant\MailingList\MailingListSubscriptionManager $subscriptionManager $result */
    $subscriptionManager = \Civi::service(MailingListSubscriptionManager::class);
    try {
      $subscriptionManager->confirmSubscription($this->getToken());
      $result->exchangeArray([
        'success' => TRUE,
        'message' => E::ts('Subscription succeeded.'),
      ]);
    }
    catch (InvalidSubscriptionTokenException $e) {
      $result->exchangeArray([
        'success' => FALSE,
        'message' => E::ts('Subscription token is not valid anymore.'),
      ]);
    }
  }

}
