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

use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Tests regarding general event registration
 *
 * @group headless
 */
class CRM_Remoteevent_SessionTest extends CRM_Remoteevent_TestBase
{
    use Api3TestTrait {
        callAPISuccess as protected traitCallAPISuccess;
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test registration with sessions
     */
    public function testSimpleRegistration()
    {
        // create an event
        $event = $this->createRemoteEvent([]);
        $session1 = $this->createEventSession($event['id']);
        $session2 = $this->createEventSession($event['id']);

        // register one contact
        $contactA = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], [
            'email'                    => $contactA['email'],
            "session{$session1['id']}" => 1,
            "session{$session2['id']}" => 0,
        ]);
        $this->assertEmpty($registration1['is_error'], "Registration Failed");
        $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($registration1['participant_id']);
        $this->assertTrue(in_array($session1['id'], $registered_session_ids), "Participant should be registered for session 1");
        $this->assertFalse(in_array($session2['id'], $registered_session_ids), "Participant should NOT be registered for session 2");
    }

    /**
     * Test form prefill
     */
    public function testSessionPrefill()
    {
        // create an event
        $event = $this->createRemoteEvent([]);
        $session1 = $this->createEventSession($event['id']);
        $session2 = $this->createEventSession($event['id']);
        $session3 = $this->createEventSession($event['id']);

        // register one contact
        $contactA = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], [
            'email'                    => $contactA['email'],
            "session{$session1['id']}" => 1,
            "session{$session2['id']}" => 1,
            "session{$session3['id']}" => 0,
        ]);
        $this->assertEmpty($registration1['is_error'], "Registration Failed");

        // see if the fields are prefilled for an update
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $registration1['participant_id'], null, 'update');
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'token'    => $token,
            'context'  => 'update',
            'event_id' => $event['id'],
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertNotEmpty($fields["session{$session1['id']}"]['value'], "Session [{$session1['id']}] was not pre-filled");
        $this->assertNotEmpty($fields["session{$session2['id']}"]['value'], "Session [{$session2['id']}] was not pre-filled");
        $this->assertTrue(empty($fields["session{$session3['id']}"]['value']), "Session [{$session3['id']}] was pre-filled with 1");
    }

    /**
     * Test registration with sessions
     */
    public function testMaxParticipants()
    {
        // create an event
        $event = $this->createRemoteEvent([]);
        $session = $this->createEventSession($event['id'], [
            'max_participants' => 1
        ]);

        // register one contact
        $contactA = $this->createContact();
        $registrationA = $this->registerRemote($event['id'], [
            'email'                   => $contactA['email'],
            "session{$session['id']}" => 1,
        ]);
        $this->assertEmpty($registrationA['is_error'], "Registration Failed");
        $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($registrationA['participant_id']);
        $this->assertTrue(in_array($session['id'], $registered_session_ids), "Participant should be registered for session");

        // register another contact
        $contactB = $this->createContact();
        $registrationB = $this->registerRemote($event['id'], [
            'email'                   => $contactB['email'],
            "session{$session['id']}" => 1,
        ]);
        $this->assertNotEmpty($registrationB['is_error'], "Registration should have failed Failed");
        $this->assertEquals('Session is full', $registrationB['error_message'], "The registration should have failed the session was full.");
    }

}
