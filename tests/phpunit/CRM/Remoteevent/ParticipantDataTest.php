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

use Civi\RemoteParticipant\Event\RegistrationEvent as RegistrationEvent;

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Tests regarding the creation / update of participants
 *
 * @group headless
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_Remoteevent_ParticipantDataTest extends CRM_Remoteevent_TestBase {

  /**
   * event hook for testAnonymousRegistration */
  public static function registrationSetParticipantCampaign(RegistrationEvent $registration) {
    $campaign_id = $registration->getSubmission()['campaign_id'] ?? NULL;
    if ($campaign_id) {
      $participant = &$registration->getParticipantData();
      $participant['campaign_id'] = $campaign_id;
    }
  }

  /**
   * Test registration with a waiting list
   */
  public function testAnonymousRegistration() {
    // create an event
    $event = $this->createRemoteEvent([
      'event_remote_registration.remote_registration_default_profile' => 'Standard1',
    ]);

    Civi::dispatcher()->addListener(
      RegistrationEvent::NAME,
      ['CRM_Remoteevent_ParticipantDataTest', 'registrationSetParticipantCampaign'],
      CRM_Remoteevent_Registration::BEFORE_PARTICIPANT_CREATION + 20
    );

    // register one contact
    $contact = $this->createContact();
    $campaign = $this->getCampaign();
    $registration = $this->registerRemote($event['id'], [
      'email' => $contact['email'],
      'campaign_id' => $campaign['id'],
    ]);
    self::assertEmpty($registration['is_error'], 'First Registration Failed');

    // load the participant
    $participant = $this->traitCallAPISuccess('Participant', 'getsingle', [
      'contact_id' => $contact['id'],
      'event_id'   => $event['id'],
    ]);
    self::assertEquals(
      $campaign['id'],
      $participant['participant_campaign_id'],
      'The campaign was not propagated to the participant!'
    );
  }

  /**
   * Test registration with a waiting list
   */
  public function testInvitedRegistration() {
    // create an event
    $event = $this->createRemoteEvent([
      'event_remote_registration.remote_registration_default_profile' => 'Standard1',
    ]);

    Civi::dispatcher()->addListener(
      RegistrationEvent::NAME,
      ['CRM_Remoteevent_ParticipantDataTest', 'registrationSetParticipantCampaign'],
      CRM_Remoteevent_Registration::BEFORE_PARTICIPANT_CREATION + 20
    );

    // create invite participant
    $contact = $this->createContact();
    $result = $this->traitCallAPISuccess(
        'Participant',
        'create',
        [
          'event_id' => $event['id'],
          'contact_id' => $contact['id'],
          'status_id' => $this->getParticipantInvitedStatus(),
          'role_id' => 'Attendee',
        ]
    );
    $participant_id = $result['id'];
    $this->assertParticipantStatus($participant_id, 'Invited', "Participant status should be 'Invited'");

    // generate token
    $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, NULL, 'invite');

    // register one contact
    $campaign = $this->getCampaign();
    $registration = $this->registerRemote($event['id'], [
      'token' => $token,
      'confirm' => 1,
      'email' => $contact['email'],
      'campaign_id' => $campaign['id'],
    ]);
    self::assertEmpty($registration['is_error'], 'First Registration Failed');

    // load the participant
    $participant = $this->traitCallAPISuccess('Participant', 'getsingle', [
      'contact_id' => $contact['id'],
      'event_id'   => $event['id'],
    ]);
    self::assertEquals(
      $campaign['id'],
      $participant['participant_campaign_id'],
      'The campaign was not propagated to the participant!'
    );
  }

}
