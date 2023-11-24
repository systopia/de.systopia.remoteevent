<?php
/*
 * Copyright (C) 2023 SYSTOPIA GmbH
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

declare(strict_types=1);

namespace Civi\RemoteParticipant;

use Civi\RemoteParticipant\Event\RegistrationEvent;

final class RegistrationEventFactory
{
    public function createRegistrationEvent(array $submission_data): RegistrationEvent {
        $registrationEvent = new RegistrationEvent($submission_data);
        $profile = \CRM_Remoteevent_RegistrationProfile::getProfile($registrationEvent);
        $event = $registrationEvent->getEvent();

        $contactData = [];
        $participantData = [];
        foreach ($profile->getFields() as $fieldKey => $fieldSpec) {
            if (isset($submission_data[$fieldKey])) {
                $entity_names = (array) ($fieldSpec['entity_name'] ?? $profile->getFieldEntities($fieldKey));
                $entity_field_name = $fieldSpec['entity_field_name'] ?? $fieldKey;
                $value = isset($fieldSpec['value_callback'])
                  ? $fieldSpec['value_callback']($submission_data[$fieldKey], $submission_data)
                  : $submission_data[$fieldKey];

                if (in_array('Contact', $entity_names, TRUE)) {
                    $contactData[$entity_field_name] = $value;
                }
                if (in_array('Participant', $entity_names, TRUE)) {
                    $participantData[$entity_field_name] = $value;
                }
            }
        }

        $participantData['role_id'] ??= $this->getDefaultRoleId($event);
        $participantData['event_id'] = $submission_data['event_id'];

        $profile->modifyContactData($contactData);

        $registrationEvent->setContactData($contactData);
        $registrationEvent->setParticipant($participantData);

        // Create additional participants' data based on submission.
        $registrationEvent->setAdditionalParticipantsData(
          $this->getAdditionalParticipantsData($submission_data, $event)
        );

        return $registrationEvent;
    }

    /**
     * @phpstan-param array<string, mixed> $submissionData
     * @phpstan-param array<string, mixed> $event
     *
     * @phpstan-return array<array<string, mixed>>
     */
    private function getAdditionalParticipantsData(array $submissionData, array $event): array {
        $additionalParticipantsData = [];
        foreach ($submissionData as $key => $value) {
            $additionalParticipantMatches =  [];
            if (preg_match('#^additional_([0-9]+)_(.*?)$#', $key, $additionalParticipantMatches)) {
                [, $participantNo, $fieldName] = $additionalParticipantMatches;
                if ($participantNo <= $event['max_additional_participants']) {
                    $additionalParticipantsData[$participantNo][$fieldName] = $value;
                } else {
                    throw new \Exception('Maximum number of additional participants exceeded');
                }
            }
        }

        foreach ($additionalParticipantsData as &$additionalParticipantData) {
            $additionalParticipantData['role_id'] ??= $this->getDefaultRoleId($event);
        }

        return $additionalParticipantsData;
    }

    /**
     * @param array<string, mixed> $event
     */
    private function getDefaultRoleId(array $event): int {
        return (int) ($event['default_role_id'] ?: 1);  // 1 = Attendee
    }
}
