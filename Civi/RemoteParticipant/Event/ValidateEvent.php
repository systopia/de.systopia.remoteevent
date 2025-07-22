<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

declare(strict_types = 1);

namespace Civi\RemoteParticipant\Event;

use Civi\RemoteEvent;
use Civi\RemoteParticipant\Event\Util\PriceFieldUtil;
use Civi\RemoteParticipant\RegistrationEventFactory;
use CRM_Remoteevent_RegistrationProfile;

/**
 * Class ValidateEvent
 *
 * @package Civi\RemoteParticipant\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteParticipant.validate API call, so the search parameters can be manipulated
 */
class ValidateEvent extends RemoteEvent {

  public const NAME = 'civi.remoteevent.registration.validate';

  /**
   * @phpstan-return array<string, mixed>
   */
  protected array $submission;

  public function __construct($submission_data, $error_list = []) {
    $this->submission = $submission_data;
    $this->error_list = $error_list;
    $this->token_usages = ['invite', 'update'];
  }

  /**
   * {@inheritDoc}
   */
  public function getQueryParameters(): array {
    return $this->submission;
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function getSubmission(): array {
    return $this->submission;
  }

  public function addValidationError(string $fieldName, string $errorMessage): void {
    $this->addError($errorMessage, $fieldName);
  }

  /**
   * @phpstan-return list<string>
   */
  public function &modifyValidationErrors(): array {
    return $this->error_list;
  }

  public function getAdditionalParticipantsCount(): int {
    return array_reduce(
      preg_grep('#^additional_([0-9]+)(_|$)#', array_keys($this->getSubmission())),
      function(int $carry, string $item) {
        $currentCount = (int) preg_filter('#^additional_([0-9]+)(.*?)$#', '$1', $item);
        return max($carry, $currentCount);
      },
      0
    );
  }

  public function getRequestedParticipantCount(int $additionalParticipantsCount): int {
    $event = $this->getEvent();
    $submission = $this->getSubmission();
    $priceFields = PriceFieldUtil::getPriceFields($event);
    $maxRequestedParticipantCounts = [];
    foreach (CRM_Remoteevent_RegistrationProfile::getPriceFieldsToValidate(
      $priceFields,
      $additionalParticipantsCount
    ) as $fieldName => $priceFieldId) {
      /** @var $participantNo 0 for the initial participant, 1-N for additional participants */
      $participantNo = RegistrationEventFactory::getAdditionalParticipantNo($fieldName) ?? 0;
      $priceField = $priceFields[$priceFieldId];
      $priceFieldValues = PriceFieldUtil::getPriceFieldValues($priceField['price_field.id']);
      $priceFieldValueId = $priceField['price_field.is_enter_qty']
        ? array_key_first($priceFieldValues)
        : (int) $submission[$fieldName];

      // For each participant (initial and additional), use the maximum count of participants in price fields to be used
      // for this registration.
      $maxRequestedParticipantCounts[$participantNo] = max(
        $priceFieldValues[$priceFieldValueId]['count'] ?? 1,
        $maxRequestedParticipantCounts[$participantNo]
      );
    }

    return array_sum($maxRequestedParticipantCounts);
  }

}
