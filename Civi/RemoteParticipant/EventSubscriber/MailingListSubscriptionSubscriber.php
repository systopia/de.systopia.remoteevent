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

use Civi\Api4\Group;
use Civi\RemoteParticipant\Event\ChangingEvent;
use Civi\RemoteParticipant\Event\GetCreateParticipantFormEvent;
use Civi\RemoteParticipant\Event\GetParticipantFormEventBase;
use Civi\RemoteParticipant\Event\GetUpdateParticipantFormEvent;
use Civi\RemoteParticipant\Event\RegistrationEvent;
use Civi\RemoteParticipant\Event\UpdateEvent;
use Civi\RemoteParticipant\Event\ValidateEvent;
use Civi\RemoteParticipant\MailingList\MailingListSubscriptionManager;
use Civi\RemoteTools\Api4\Api4Interface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MailingListSubscriptionSubscriber implements EventSubscriberInterface {

  private Api4Interface $api4;

  private MailingListSubscriptionManager $subscriptionManager;

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      GetCreateParticipantFormEvent::NAME => ['onGetCreateParticipantForm', -1],
      GetUpdateParticipantFormEvent::NAME => ['onGetUpdateParticipantForm', -1],
      ValidateEvent::NAME => ['onValidateEvent', -1],
      RegistrationEvent::NAME => ['onRegistrationEvent', \CRM_Remoteevent_Registration::AFTER_PARTICIPANT_CREATION],
      UpdateEvent::NAME => ['onUpdateEvent', \CRM_Remoteevent_RegistrationUpdate::AFTER_APPLY_PARTICIPANT_CHANGES],
    ];
  }

  public function __construct(Api4Interface $api4, MailingListSubscriptionManager $subscriptionManager) {
    $this->api4 = $api4;
    $this->subscriptionManager = $subscriptionManager;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function onGetCreateParticipantForm(GetCreateParticipantFormEvent $event): void {
    $this->addFields($event);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function onGetUpdateParticipantForm(GetUpdateParticipantFormEvent $event): void {
    $this->addFields($event);
  }

  public function onValidateEvent(ValidateEvent $event): void {
    /**
     * @phpstan-var array<int, string> $groupIds
     *   Mapping of group ID as integer to group ID as string.
     */
    $groupIds = $event->getSubmission()['mailing_list_group_ids'] ?? [];
    /** @phpstan-var list<int> $allowedGroupIds */
    $allowedGroupIds = $event->getEvent()['event_remote_registration.mailing_list_group_ids'] ?? [];
    if ([] !== array_diff($groupIds, $allowedGroupIds)) {
      $l10n = $event->getLocalisation();
      $event->addValidationError('mailing_list_group_ids', $l10n->ts('Invalid value'));
    }
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function onRegistrationEvent(RegistrationEvent $event): void {
    $this->subscribeToMailingLists($event);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function onUpdateEvent(UpdateEvent $event): void {
    $this->subscribeToMailingLists($event);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  private function addFields(GetParticipantFormEventBase $event): void {
    $groupIds = $event->getEvent()['event_remote_registration.mailing_list_group_ids'] ?? [];
    if ([] === $groupIds) {
      return;
    }

    $groups = $this->api4->execute('Group', 'get', [
      'select' => ['id', 'title'],
      'where' => [
        ['id', 'IN', $groupIds],
        ['is_active', '=', TRUE],
      ],
      'orderBy' => ['title' => 'ASC'],
    ])->indexBy('id')
      ->column('title');
    if ([] === $groups) {
      return;
    }

    $label = $event->getEvent()['event_remote_registration.mailing_list_subscriptions_label'] ?? '';
    if ('' === $label) {
      $label = $event->getLocalisation()->ts('I want to subscribe to the following mailing lists.');
    }

    $maxWeight = 0;
    foreach ($event->getResult() as $resultField) {
      $maxWeight = max($maxWeight, $resultField['weight'] ?? 0);
    }

    $event->addFields([
      'mailing_list_group_ids' => [
        'name' => 'mailing_list_group_ids',
        'entity_name' => 'Custom',
        'type' => 'Multi-Select',
        'options' => $groups,
        'label' => $label,
        'weight' => $maxWeight,
      ],
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  private function subscribeToMailingLists(ChangingEvent $event): void {
    /**
     * @phpstan-var array<int, string> $groupIds
     *   Mapping of group ID as integer to group ID as string.
     */
    $groupIds = $event->getSubmission()['mailing_list_group_ids'] ?? [];
    $contactId = (int) $event->getContactID();

    if ([] === $groupIds || $contactId <= 0) {
      return;
    }

    /** @var bool $doubleOptIn */
    $doubleOptIn = $event->getEvent()['event_remote_registration.is_mailing_list_double_optin'] ?? FALSE;
    if ($doubleOptIn) {
      /** @var string $subject */
      // @phpstan-ignore offsetAccess.notFound
      $subject = $event->getEvent()['event_remote_registration.mailing_list_double_optin_subject'];
      /** @var string $text */
      // @phpstan-ignore offsetAccess.notFound
      $text = $event->getEvent()['event_remote_registration.mailing_list_double_optin_text'];
    }

    foreach ($groupIds as $groupId) {
      if ($doubleOptIn) {
        $this->subscriptionManager->subscribeWithDoubleOptIn($contactId, (int) $groupId, $subject, $text);
      }
      else {
        $this->subscriptionManager->subscribe($contactId, (int) $groupId);
      }
    }
  }

}
