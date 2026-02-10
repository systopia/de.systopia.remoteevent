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

/**
 * Tests regarding general event registration
 *
 * @group headless
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_Remoteevent_SessionTest extends CRM_Remoteevent_TestBase {

  /**
   * Test registration with sessions
   */
  public function testSimpleRegistration() {
    // create an event
    $event = $this->createRemoteEvent([]);
    $session1 = $this->createEventSession($event['id']);
    $session2 = $this->createEventSession($event['id']);

    // register one contact
    $contactA = $this->createContact();
    $registration1 = $this->registerRemote(
      $event['id'],
      [
        'email' => $contactA['email'],
        "session{$session1['id']}" => 1,
        "session{$session2['id']}" => 0,
      ]
    );
    $this->assertEmpty($registration1['is_error'], 'Registration Failed');
    $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations(
      $registration1['participant_id']
    );
    $this->assertTrue(
      in_array($session1['id'], $registered_session_ids),
      'Participant should be registered for session 1'
    );
    $this->assertFalse(
      in_array($session2['id'], $registered_session_ids),
      'Participant should NOT be registered for session 2'
    );
  }

  /**
   * Test form prefill
   */
  public function testSessionPrefill() {
    // create an event
    $event = $this->createRemoteEvent([]);
    $session1 = $this->createEventSession($event['id']);
    $session2 = $this->createEventSession($event['id']);
    $session3 = $this->createEventSession($event['id']);

    // register one contact
    $contactA = $this->createContact();
    $registration1 = $this->registerRemote(
      $event['id'],
      [
        'email' => $contactA['email'],
        "session{$session1['id']}" => 1,
        "session{$session2['id']}" => 1,
        "session{$session3['id']}" => 0,
      ]
    );
    $this->assertEmpty($registration1['is_error'], 'Registration Failed');

    // see if the fields are prefilled for an update
    $token = CRM_Remotetools_SecureToken::generateEntityToken(
      'Participant',
      $registration1['participant_id'],
      NULL,
      'update'
    );
    $fields = $this->traitCallAPISuccess(
      'RemoteParticipant',
      'get_form',
      [
        'token' => $token,
        'context' => 'update',
        'event_id' => $event['id'],
      ]
    )['values'];
    $this->assertGetFormStandardFields($fields, TRUE);
    $this->assertNotEmpty(
      $fields["session{$session1['id']}"]['value'],
      "Session [{$session1['id']}] was not pre-filled"
    );
    $this->assertNotEmpty(
      $fields["session{$session2['id']}"]['value'],
      "Session [{$session2['id']}] was not pre-filled"
    );
    $this->assertTrue(
      empty($fields["session{$session3['id']}"]['value']),
      "Session [{$session3['id']}] was pre-filled with 1"
    );
  }

  /**
   * Test registration with sessions
   */
  public function testMaxParticipants() {
    // create an event
    $event = $this->createRemoteEvent([]);
    $session = $this->createEventSession(
      $event['id'],
      [
        'max_participants' => 1,
      ]
    );

    // register one contact
    $contactA = $this->createContact();
    $registrationA = $this->registerRemote(
      $event['id'],
      [
        'email' => $contactA['email'],
        "session{$session['id']}" => 1,
      ]
    );
    $this->assertEmpty($registrationA['is_error'], 'Registration Failed');
    $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations(
      $registrationA['participant_id']
    );
    $this->assertTrue(
      in_array($session['id'], $registered_session_ids),
      'Participant should be registered for session'
    );

    // register another contact
    $contactB = $this->createContact();
    $registrationB = $this->registerRemote(
      $event['id'],
      [
        'email' => $contactB['email'],
        "session{$session['id']}" => 1,
      ]
    );
    $this->assertNotEmpty($registrationB['is_error'], 'Registration should have failed Failed');
    $this->assertEquals(
      'Session is full',
      $registrationB['error_message'],
      'The registration should have failed the session was full.'
    );
  }

  /**
   * Test registration update from one max 1 session to another
   */
  public function testMaxParticipantsRegistrationUpdate() {
    // create an event
    $event = $this->createRemoteEvent([]);
    $sessionA = $this->createEventSession(
      $event['id'],
      [
        'max_participants' => 1,
      ]
    );
    $sessionB = $this->createEventSession(
      $event['id'],
      [
        'max_participants' => 1,
      ]
    );

    // register one contact
    $contactA = $this->createContact();
    $registrationA = $this->registerRemote(
      $event['id'],
      [
        'email' => $contactA['email'],
        "session{$sessionA['id']}" => 1,
      ]
    );
    $this->assertEmpty($registrationA['is_error'], 'Registration Failed');
    $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations(
      $registrationA['participant_id']
    );
    $this->assertTrue(
      in_array($sessionA['id'], $registered_session_ids),
      'Participant should be registered for session'
    );

    // update registration
    $registrationB = $this->updateRegistration(
      [
        'participant_id' => $registrationA['participant_id'],
        'email' => $contactA['email'],
        "session{$sessionB['id']}" => 1,
      ]
    );
    $this->assertEmpty($registrationA['is_error'], 'Registration Failed');
    $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations(
      $registrationA['participant_id']
    );
    $this->assertTrue(
      in_array($sessionB['id'], $registered_session_ids),
      'Participant should now be registered for session B'
    );
    $this->assertFalse(
      in_array($sessionA['id'], $registered_session_ids),
      'Participant should not be registered for session A any more'
    );
  }

  /**
   * Test registration update from one max 1 session to another
   */
  public function testSessionEventInfoData() {
    // create an event with sessions
    $event = $this->createRemoteEvent([]);
    $sessionA = $this->createEventSession(
      $event['id'],
      [
        'max_participants' => 1,
      ]
    );
    $sessionB = $this->createEventSession(
      $event['id'],
      [
        'max_participants' => 1,
      ]
    );

    // test with the setting turned OFF
    Civi::settings()->set('remote_event_get_session_data', FALSE);
    $remote_event = $this->getRemoteEvent($event['id']);
    $this->assertArrayNotHasKey(
      'sessions',
      $remote_event,
      "When submitting session data is disabled, the 'sessions' key should not be there"
    );

    // test with the setting turned ON
    Civi::settings()->set('remote_event_get_session_data', TRUE);
    $remote_event = $this->getRemoteEvent($event['id']);
    $this->assertArrayHasKey(
      'sessions',
      $remote_event,
      "When submitting session data is enabled, the 'sessions' key should be there"
    );
    $event_sessions = json_decode($remote_event['sessions'], TRUE);
    $this->assertNotNull($event_sessions, "Couldn't decode session json");

    // check speakers and get speaker IDs
    foreach ($event_sessions as $event_session_fields) {
      $event_session = $this->mapFieldArray($event_session_fields);
      $this->assertNotEmpty($event_session['title'], 'Session should have a title');
      $this->assertNotEmpty($event_session['start_date'], 'Session should have a start_date');
      $this->assertNotEmpty($event_session['end_date'], 'Session should have a end_date');
      $this->assertNotEmpty($event_session['day'], 'Session should have a day');
      $this->assertNotEmpty($event_session['category'], 'Session should have a category');
      $this->assertNotEmpty($event_session['type'], 'Session should have a type');
    }
  }

}
