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

use Civi\Api4\Generic\Result;
use Civi\Api4\GroupContact;
use Civi\RemoteEvent\Exception\InvalidSubscriptionTokenException;
use Civi\RemoteTools\Api4\Api4;
use Civi\RemoteTools\Api4\Api4Interface;
use Civi\RemoteTools\Api4\Query\Comparison;
use Civi\RemoteTools\Api4\Query\CompositeCondition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\RemoteParticipant\MailingList\MailingListSubscriptionManager
 */
final class MailingListSubscriptionManagerTest extends TestCase {

  /**
   * @var \Civi\RemoteTools\Api4\Api4Interface&\PHPUnit\Framework\MockObject\MockObject
   */
  private MockObject $api4Mock;

  /**
   * @var \Civi\RemoteParticipant\MailingList\DoubleOptInEmailSender&\PHPUnit\Framework\MockObject\MockObject
   */
  private MockObject $doubleOptInEmailSenderMock;

  private MailingListSubscriptionManager $subscriptionManager;

  /**
   * @var \Civi\RemoteParticipant\MailingList\DoubleOptInTokenGenerator&\PHPUnit\Framework\MockObject\MockObject
   */
  private MockObject $tokenGeneratorMock;

  protected function setUp(): void {
    parent::setUp();
    $this->api4Mock = $this->createMock(Api4Interface::class);
    $this->doubleOptInEmailSenderMock = $this->createMock(DoubleOptInEmailSender::class);
    $this->tokenGeneratorMock = $this->createMock(DoubleOptInTokenGenerator::class);
    $this->subscriptionManager = new MailingListSubscriptionManager(
      $this->api4Mock,
      $this->doubleOptInEmailSenderMock,
      $this->tokenGeneratorMock
    );
  }

  public function testConfirmSubscription(): void {
    $this->api4Mock->method('getEntities')->with(
      'GroupContact',
      Comparison::new('remote_event_mailing_list.token', '=', 'token'),
      [],
      2
    )->willReturn(new Result([
      [
        'id' => 2,
        'status' => 'Pending',
        'group_id' => 3,
        'contact_id' => 4,
      ],
    ]));

    $this->api4Mock->expects(static::once())->method('updateEntity')->with(
      'GroupContact', 2, ['status' => 'Added', 'remote_event_mailing_list.token' => NULL],
    )->willReturn(new Result([]));
    $this->subscriptionManager->confirmSubscription('token');
  }

  public function testConfirmSubscriptionInvalidToken(): void {
    $this->api4Mock->method('getEntities')->with(
      'GroupContact',
      Comparison::new('remote_event_mailing_list.token', '=', 'token'),
      [],
      2
    )->willReturn(new Result([]));

    $this->expectException(InvalidSubscriptionTokenException::class);
    $this->subscriptionManager->confirmSubscription('token');
  }

  public function testConfirmSubscriptionNonUniqueToken(): void {
    $this->api4Mock->method('getEntities')->with(
      'GroupContact',
      Comparison::new('remote_event_mailing_list.token', '=', 'token'),
      [],
      2
    )->willReturn(new Result([
      ['id' => 1],
      ['id' => 2],
    ]));

    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Duplicate subscription token "token"');
    $this->subscriptionManager->confirmSubscription('token');
  }

  public function testSubscribe(): void {
    $this->api4Mock->expects(static::once())->method('execute')
      ->with('GroupContact', 'save', [
        'records' => [
          [
            'contact_id' => 23,
            'group_id' => 2,
            'status' => 'Added',
          ],
        ],
        'match' => [
          'contact_id',
          'group_id',
        ],
      ])->willReturn(new Result([]));

    $this->subscriptionManager->subscribe(23, 2);
  }

  public function testSubscribeWithDoubleOptInWithoutSubscription(): void {
    $this->api4Mock->method('execute')->with(
      'GroupContact', 'get', [
        'select' => ['*', 'remote_event_mailing_list.token'],
        'where' => [
          ['contact_id', '=', 23],
          ['group_id', '=', 2],
        ],
      ]
    )->willReturn(new Result([]));

    $this->tokenGeneratorMock->method('generateToken')->willReturn('the_token');

    $this->api4Mock->expects(static::once())->method('createEntity')->with(
      'GroupContact', [
        'contact_id' => 23,
        'group_id' => 2,
        'status' => 'Pending',
        'remote_event_mailing_list.token' => 'the_token',
      ]
    )->willReturn(new Result([]));

    $this->doubleOptInEmailSenderMock->expects(static::once())->method('sendEmail')->with(
      23, 2, 'the_token', 'Subject', 'Text'
    );

    $this->subscriptionManager->subscribeWithDoubleOptIn(23, 2, 'Subject', 'Text');
  }

  public function testSubscribeWithDoubleOptInSubscriptionPending(): void {
    $this->api4Mock->method('execute')->with(
      'GroupContact', 'get', [
        'select' => ['*', 'remote_event_mailing_list.token'],
        'where' => [
          ['contact_id', '=', 23],
          ['group_id', '=', 2],
        ],
      ]
    )->willReturn(new Result([
      [
        'id' => 1,
        'group_id' => 2,
        'contact_id' => 23,
        'status' => 'Pending',
        'remote_event_mailing_list.token' => 'existing_token',
      ],
    ]));

    $this->api4Mock->expects(static::once())->method('updateEntity')->with(
      'GroupContact', 1, ['status' => 'Pending']
    );

    $this->doubleOptInEmailSenderMock->expects(static::once())->method('sendEmail')->with(
      23, 2, 'existing_token', 'Subject', 'Text'
    );

    $this->subscriptionManager->subscribeWithDoubleOptIn(23, 2, 'Subject', 'Text');
  }

  public function testSubscribeWithDoubleOptInSubscriptionPendingWithoutToken(): void {
    $this->api4Mock->method('execute')->with(
      'GroupContact', 'get', [
        'select' => ['*', 'remote_event_mailing_list.token'],
        'where' => [
          ['contact_id', '=', 23],
          ['group_id', '=', 2],
        ],
      ]
    )->willReturn(new Result([
      [
        'id' => 1,
        'group_id' => 2,
        'contact_id' => 23,
        'status' => 'Pending',
        'remote_event_mailing_list.token' => NULL,
      ],
    ]));

    $this->tokenGeneratorMock->method('generateToken')->willReturn('new_token');

    $this->api4Mock->expects(static::once())->method('updateEntity')->with(
      'GroupContact', 1, [
        'status' => 'Pending',
        'remote_event_mailing_list.token' => 'new_token',
      ])->willReturn(new Result([]));

    $this->doubleOptInEmailSenderMock->expects(static::once())->method('sendEmail')->with(
      23, 2, 'new_token', 'Subject', 'Text'
    );

    $this->subscriptionManager->subscribeWithDoubleOptIn(23, 2, 'Subject', 'Text');
  }

  public function testSubscribeWithDoubleOptInSubscriptionExists(): void {
    $this->api4Mock->method('execute')->with(
      'GroupContact', 'get', [
        'select' => ['*', 'remote_event_mailing_list.token'],
        'where' => [
          ['contact_id', '=', 23],
          ['group_id', '=', 2],
        ],
      ]
    )->willReturn(new Result([
      [
        'id' => 1,
        'group_id' => 2,
        'contact_id' => 23,
        'status' => 'Added',
        'remote_event_mailing_list.token' => 'existing_token',
      ],
    ]));

    $this->api4Mock->expects(static::never())->method('createEntity');
    $this->api4Mock->expects(static::never())->method('updateEntity');
    $this->doubleOptInEmailSenderMock->expects(static::never())->method('sendEmail');

    $this->subscriptionManager->subscribeWithDoubleOptIn(23, 2, 'Subject', 'Text');
  }

}
