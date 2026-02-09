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

namespace Civi\RemoteParticipant\EventSubscriber;

use Civi\Api4\Generic\Result;
use Civi\RemoteParticipant\Event\GetCreateParticipantFormEvent;
use Civi\RemoteParticipant\Event\GetUpdateParticipantFormEvent;
use Civi\RemoteParticipant\Event\RegistrationEvent;
use Civi\RemoteParticipant\Event\UpdateEvent;
use Civi\RemoteParticipant\Event\ValidateEvent;
use Civi\RemoteParticipant\MailingList\MailingListSubscriptionManager;
use Civi\RemoteTools\Api4\Api4Interface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\RemoteParticipant\EventSubscriber\MailingListSubscriptionSubscriber
 */
final class MailingListSubscriptionSubscriberTest extends TestCase {

  /**
   * @var \Civi\RemoteTools\Api4\Api4Interface&\PHPUnit\Framework\MockObject\MockObject
   */
  private MockObject $api4Mock;

  /**
   * @var \Civi\RemoteParticipant\MailingList\MailingListSubscriptionManager&\PHPUnit\Framework\MockObject\MockObject
   */
  private MockObject $subscriptionManagerMock;

  private MailingListSubscriptionSubscriber $subscriber;

  protected function setUp(): void {
    parent::setUp();
    $this->api4Mock = $this->createMock(Api4Interface::class);
    $this->subscriptionManagerMock = $this->createMock(MailingListSubscriptionManager::class);
    $this->subscriber = new MailingListSubscriptionSubscriber(
    $this->api4Mock,
    $this->subscriptionManagerMock
    );
  }

  public function testGetSubscribedEvents(): void {
    $expectedSubscriptions = [
      GetCreateParticipantFormEvent::NAME => ['onGetCreateParticipantForm', -1],
      GetUpdateParticipantFormEvent::NAME => ['onGetUpdateParticipantForm', -1],
      ValidateEvent::NAME => ['onValidateEvent', -1],
      RegistrationEvent::NAME => ['onRegistrationEvent', \CRM_Remoteevent_Registration::AFTER_PARTICIPANT_CREATION],
      UpdateEvent::NAME => ['onUpdateEvent', \CRM_Remoteevent_RegistrationUpdate::AFTER_APPLY_PARTICIPANT_CHANGES],
    ];

    static::assertEquals($expectedSubscriptions, $this->subscriber::getSubscribedEvents());

    foreach ($expectedSubscriptions as [$method, $priority]) {
      static::assertTrue(method_exists(get_class($this->subscriber), $method));
    }
  }

  /**
   * @phpstan-param array<string, array<string, mixed>> $initialFields
   *
   * @dataProvider provideInitialFields()
   */
  public function testOnGetCreateParticipantForm(array $initialFields, int $expectedWeight): void {
    $event = new GetCreateParticipantFormEvent([], [
      'event_remote_registration.mailing_list_group_ids' => [2, 3],
      'event_remote_registration.mailing_list_subscriptions_label' => 'Test Label',
    ]);

    $event->addFields($initialFields);

    $this->api4Mock->method('execute')
      ->with('Group', 'get', [
        'select' => ['id', 'title'],
        'where' => [
            ['id', 'IN', [2, 3]],
            ['is_active', '=', TRUE],
        ],
        'orderBy' => ['title' => 'ASC'],
      ])
      ->willReturn(new Result([['id' => 2, 'title' => 'Group2']]));

    $this->subscriber->onGetCreateParticipantForm($event);
    static::assertEquals($initialFields + [
      'mailing_list_group_ids' => [
        'name' => 'mailing_list_group_ids',
        'entity_name' => 'Custom',
        'label' => 'Test Label',
        'type' => 'Multi-Select',
        'options' => [2 => 'Group2'],
        'weight' => $expectedWeight,
      ],
    ], $event->getResult());
  }

  /**
   * @phpstan-param array<string, array<string, mixed>> $initialFields
   *
   * @dataProvider provideInitialFields()
   */
  public function testOnGetUpdateParticipantForm(array $initialFields, int $expectedWeight): void {
    $event = new GetUpdateParticipantFormEvent([], [
      'event_remote_registration.mailing_list_group_ids' => [2, 3],
      'event_remote_registration.mailing_list_subscriptions_label' => 'Test Label',
    ]);

    $event->addFields($initialFields);

    $this->api4Mock->method('execute')
      ->with('Group', 'get', [
        'select' => ['id', 'title'],
        'where' => [
          ['id', 'IN', [2, 3]],
          ['is_active', '=', TRUE],
        ],
        'orderBy' => ['title' => 'ASC'],
      ])
      ->willReturn(new Result([['id' => 2, 'title' => 'Group2']]));

    $this->subscriber->onGetUpdateParticipantForm($event);
    static::assertEquals($initialFields + [
      'mailing_list_group_ids' => [
        'name' => 'mailing_list_group_ids',
        'entity_name' => 'Custom',
        'label' => 'Test Label',
        'type' => 'Multi-Select',
        'options' => [2 => 'Group2'],
        'weight' => $expectedWeight,
      ],
    ], $event->getResult());
  }

  /**
   * @phpstan-return iterable<array{array<string, array<string, mixed>>, int}>
   */
  public function provideInitialFields(): iterable {
    yield [
      [],
      0,
    ];

    yield [
      [
        'foo' => [
          'name' => 'foo',
          'type' => 'Text',
        ],
      ],
      0,
    ];

    yield [
      [
        'foo' => [
          'name' => 'foo',
          'type' => 'Text',
          'weight' => 1,
        ],
        'bar' => [
          'name' => 'bar',
          'type' => 'Text',
          'weight' => 10,
        ],
      ],
      10,
    ];
  }

  public function testOnRegistrationEventWithoutDoubleOptIn(): void {
    $event = new RegistrationEvent(
      ['mailing_list_group_ids' => [2 => '2']],
      [
        'event_remote_registration.is_mailing_list_double_optin' => FALSE,
      ]
    );
    $event->setContactID(23);

    $this->subscriptionManagerMock->expects(static::once())->method('subscribe')
      ->with(23, 2);

    $this->subscriber->onRegistrationEvent($event);
  }

  public function testOnRegistrationEventWithDoubleOptIn(): void {
    $event = new RegistrationEvent(
      ['mailing_list_group_ids' => [2 => '2']],
      [
        'event_remote_registration.is_mailing_list_double_optin' => TRUE,
        'event_remote_registration.mailing_list_double_optin_subject' => 'Test Subject',
        'event_remote_registration.mailing_list_double_optin_text' => 'Test Text',
      ]
    );
    $event->setContactID(23);

    $this->subscriptionManagerMock->expects(static::once())->method('subscribeWithDoubleOptIn')
      ->with(23, 2, 'Test Subject', 'Test Text');

    $this->subscriber->onRegistrationEvent($event);
  }

  public function testOnUpdateEventWithoutDoubleOptIn(): void {
    $event = new UpdateEvent(
      ['mailing_list_group_ids' => [2 => '2']],
      [
        'event_remote_registration.is_mailing_list_double_optin' => FALSE,
      ]
    );
    $event->setContactID(23);

    $this->subscriptionManagerMock->expects(static::once())->method('subscribe')
      ->with(23, 2);

    $this->subscriber->onUpdateEvent($event);
  }

  public function testOnUpdateEventWithDoubleOptIn(): void {
    $event = new UpdateEvent(
      ['mailing_list_group_ids' => [2 => '2']],
      [
        'event_remote_registration.is_mailing_list_double_optin' => TRUE,
        'event_remote_registration.mailing_list_double_optin_subject' => 'Test Subject',
        'event_remote_registration.mailing_list_double_optin_text' => 'Test Text',
      ]
    );
    $event->setContactID(23);

    $this->subscriptionManagerMock->expects(static::once())->method('subscribeWithDoubleOptIn')
      ->with(23, 2, 'Test Subject', 'Test Text');

    $this->subscriber->onUpdateEvent($event);
  }

}
