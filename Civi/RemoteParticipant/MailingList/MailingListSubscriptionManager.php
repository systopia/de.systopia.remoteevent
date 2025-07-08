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

namespace Civi\RemoteParticipant\MailingList;

use Civi\Api4\GroupContact;
use Civi\RemoteEvent\Exception\InvalidSubscriptionTokenException;
use Civi\RemoteTools\Api4\Api4Interface;
use Civi\RemoteTools\Api4\Query\Comparison;

/**
 * @phpstan-type groupContactT array{
 *   id: int,
 *   contact_id: int,
 *   group_id: int,
 *   status: string,
 *   "remote_event_mailing_list.token"?: string|null,
 * }
 */
class MailingListSubscriptionManager {

  private Api4Interface $api4;

  private DoubleOptInEmailSender $doubleOptInEmailSender;

  private DoubleOptInTokenGenerator $tokenGenerator;

  public function __construct(
    Api4Interface $api4,
    DoubleOptInEmailSender $doubleOptInEmailSender,
    DoubleOptInTokenGenerator $tokenGenerator
  ) {
    $this->api4 = $api4;
    $this->doubleOptInEmailSender = $doubleOptInEmailSender;
    $this->tokenGenerator = $tokenGenerator;
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\RemoteEvent\Exception\InvalidSubscriptionTokenException
   */
  public function confirmSubscription(string $token): void {
    $result = $this->api4->getEntities(
      'GroupContact',
      Comparison::new('remote_event_mailing_list.token', '=', $token),
      [],
      2
    );

    if (count($result) > 1) {
      throw new \CRM_Core_Exception(sprintf('Duplicate subscription token "%s"', $token));
    }

    if (count($result) === 0) {
      throw new InvalidSubscriptionTokenException(sprintf('Invalid subscription token "%s"', $token));
    }

    /** @phpstan-var groupContactT $groupContact */
    $groupContact = $result[0];
    $this->api4->updateEntity('GroupContact', $groupContact['id'], [
      'status' => 'Added',
      'remote_event_mailing_list.token' => NULL,
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function subscribe(int $contactId, int $groupId): void {
    $this->api4->execute('GroupContact', 'save', [
      'records' => [
        [
          'contact_id' => $contactId,
          'group_id' => $groupId,
          'status' => 'Added',
        ]
      ],
      'match' => [
        'contact_id',
        'group_id',
      ],
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function subscribeWithDoubleOptIn(int $contactId, int $groupId, string $subject, string $text): void {
    /** @phpstan-var groupContactT|null $groupContact */
    $groupContact = $this->api4->execute('GroupContact', 'get', [
      'select' => ['*', 'remote_event_mailing_list.token'],
      'where' => [
        ['contact_id', '=', $contactId],
        ['group_id', '=', $groupId],
      ],
    ])->first();

    if (($groupContact['status'] ?? NULL) === 'Added') {
      // Already subscribed.
      return;
    }

    if (NULL === $groupContact) {
      $token = $this->tokenGenerator->generateToken();
      $this->api4->createEntity('GroupContact', [
        'contact_id' => $contactId,
        'group_id' => $groupId,
        'status' => 'Pending',
        'remote_event_mailing_list.token' => $token,
      ]);
    }
    else {
      $values = ['status' => 'Pending'];
      $token = $groupContact['remote_event_mailing_list.token'];
      if (NULL === $token) {
        $token = $this->tokenGenerator->generateToken();
        $values['remote_event_mailing_list.token'] = $token;
      }
      $this->api4->updateEntity('GroupContact', $groupContact['id'], $values);
    }

    $this->doubleOptInEmailSender->sendEmail($contactId, $groupId, $token, $subject, $text);
  }

}
