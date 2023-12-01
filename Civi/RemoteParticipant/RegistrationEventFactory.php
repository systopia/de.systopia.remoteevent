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
    public function createRegistrationEvent(array $submissionData): RegistrationEvent {
        $registrationEvent = new RegistrationEvent($submissionData);
        $profile = \CRM_Remoteevent_RegistrationProfile::getProfile($registrationEvent);
        $event = $registrationEvent->getEvent();

        $contactData = [];
        $participantData = [];
        foreach ($profile->getFields() as $fieldKey => $fieldSpec) {
            if (array_key_exists($fieldKey, $submissionData)) {
                $entityNames = (array) ($fieldSpec['entity_name'] ?? $profile->getFieldEntities($fieldKey));
                $entityFieldName = $fieldSpec['entity_field_name'] ?? $fieldKey;
                $value = isset($fieldSpec['value_callback'])
                  ? $fieldSpec['value_callback']($submissionData[$fieldKey], $submissionData)
                  : $submissionData[$fieldKey];

                if (in_array('Contact', $entityNames, TRUE)) {
                    $contactData[$entityFieldName] = $value;
                }
                if (in_array('Participant', $entityNames, TRUE)) {
                    $participantData[$entityFieldName] = $value;
                }
            }
        }

        $participantData['role_id'] ??= $this->getDefaultRoleId($event);
        $participantData['event_id'] = $submissionData['event_id'];

        $profile->modifyContactData($contactData);

        $registrationEvent->setContactData($contactData);
        $registrationEvent->setParticipant($participantData);

        $this->handleAdditionalParticipants($registrationEvent, $profile);

        return $registrationEvent;
    }

    /**
     * Handles additional participants' data in the submission data and sets the
     * resulting data in the event.
     */
    private function handleAdditionalParticipants(RegistrationEvent $registrationEvent, \CRM_Remoteevent_RegistrationProfile $profile): void {
        $event = $registrationEvent->getEvent();

        $additionalContactsData = [];
        $additionalParticipantsData = [];

        $submissionData = $registrationEvent->getSubmission();
        foreach ($profile->getAdditionalParticipantsFields($event) as $fieldKey => $fieldSpec) {
            $participantNo = $this->getAdditionalParticipantNo($fieldKey);
            if (null !== $participantNo && array_key_exists($fieldKey, $submissionData)) {
                $entityNames = (array)($fieldSpec['entity_name'] ?? $profile->getFieldEntities($fieldKey));
                $entityFieldName = $fieldSpec['entity_field_name'] ?? $fieldKey;
                $value = isset($fieldSpec['value_callback'])
                  ? $fieldSpec['value_callback']($submissionData[$fieldKey], $submissionData)
                  : $submissionData[$fieldKey];

                if (in_array('Contact', $entityNames, true)) {
                    $additionalContactsData[$participantNo][$entityFieldName] = $value;
                }
                if (in_array('Participant', $entityNames, true)) {
                    $additionalParticipantsData[$participantNo][$entityFieldName] = $value;
                }
            }
        }

        if ([] === $additionalParticipantsData) {
            return;
        }

        $additionalParticipantsProfile = \CRM_Remoteevent_RegistrationProfile::getRegistrationProfile(
          $event['event_remote_registration.remote_registration_additional_participants_profile']
        );
        foreach ($additionalContactsData as $participantNo => &$contactData) {
            $additionalParticipantsProfile->modifyContactData($contactData);
            $contactData['contact_type'] ??= 'Individual';
            $contactData['xcm_profile'] = $event['event_remote_registration.remote_registration_additional_participants_xcm_profile'];
            $additionalParticipantsData[$participantNo]['role_id'] ??= $this->getDefaultRoleId($event);
            $additionalParticipantsData[$participantNo]['event_id'] = $submissionData['event_id'];
        }

        $registrationEvent->setAdditionalContactsData($additionalContactsData);
        $registrationEvent->setAdditionalParticipantsData($additionalParticipantsData);
    }

    private function getAdditionalParticipantNo(string $fieldKey): ?int {
        $matches = [];
        if (1 === preg_match('#^additional_([0-9]+)_(.*?)$#', $fieldKey, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $event
     */
    private function getDefaultRoleId(array $event): int {
        return (int) ($event['default_role_id'] ?: 1); // 1 = Attendee
    }
}
