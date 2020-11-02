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
 * Tests regarding invitations
 *
 * @group headless
 */
class CRM_Remoteevent_InvitationTest extends CRM_Remoteevent_TestBase
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
     * Test invited (with participant) with OneClick form.
     *
     * Expected results:
     *  - participant is in status 'Invited'
     *  - get_form has field option: accept/decline invitation
     *  - "accept" -> participant in status 'Registered'
     *  - "declined" -> participant in status 'Cancelled'
     */
    public function testInvitedParticipantOneClick()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles'        => ['Standard2', 'OneClick'],
            ]
        );

        // create invite participant
        $contact = $this->createContact();
        $result = $this->traitCallAPISuccess('Participant', 'create', [
            'event_id'   => $event['id'],
            'contact_id' => $contact['id'],
            'status_id'  => $this->getParticipantInvitedStatus(),
            'role_id'    => 'Attendee'
        ]);
        $participant_id = $result['id'];
        $this->assertParticipantStatus($participant_id, 'Invited', "Participant status should be 'Invited'");

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'profile'  => 'OneClick',
            'token'    => $token,
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote($event['id'], [
            'token'   => $token,
            'confirm' => 1
        ]);
        $this->assertParticipantStatus($participant_id, 'Registered', "Participant status should be 'Registered'");



        // NOW do the same with DECLINED
        $contact2 = $this->createContact();
        $result = $this->traitCallAPISuccess('Participant', 'create', [
            'event_id'   => $event['id'],
            'contact_id' => $contact2['id'],
            'status_id'  => $this->getParticipantInvitedStatus(),
            'role_id'    => 'Attendee'
        ]);
        $participant2_id = $result['id'];
        $this->assertParticipantStatus($participant2_id, 'Invited', "Participant status should be 'Invited'");

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant2_id, null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'profile'  => 'OneClick',
            'token'    => $token,
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote($event['id'], [
            'token'   => $token,
            'confirm' => 0
        ]);
        $this->assertParticipantStatus($participant2_id, 'Cancelled', "Participant status should be 'Cancelled'");
    }

    /**
     * Test not invited (without participant) with OneClick form.
     *
     * Expected results:
     *  - participant is in status 'Invited'
     *  - get_form has field option: accept/decline invitation
     *  - "accept" -> participant in status 'Registered'
     *  - "declined" -> participant in status 'Cancelled'
     */
    public function testInvitedNoParticipantOneClick()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles'        => ['Standard2', 'OneClick'],
            ]
        );

        // create invite participant
        $contact = $this->createContact();

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Contact', $contact['id'], null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'event_id' => $event['id'],
            'profile'  => 'OneClick',
            'token'    => $token,
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote($event['id'], [
            'token'   => $token,
            'confirm' => 1
        ]);
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', [
            'event_id'   => $event['id'],
            'contact_id' => $contact['id'],
        ]);
        $this->assertParticipantStatus($participant['id'], 'Registered', "Participant status should be 'Registered'");



        // NOW do the same with DECLINED
        $contact2 = $this->createContact();
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Contact', $contact2['id'], null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'event_id' => $event['id'],
            'profile'  => 'OneClick',
            'token'    => $token,
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote($event['id'], [
            'token'   => $token,
            'confirm' => 0
        ]);
        $participant2 = $this->traitCallAPISuccess('Participant', 'getsingle', [
            'event_id'   => $event['id'],
            'contact_id' => $contact2['id'],
        ]);
        $this->assertParticipantStatus($participant2['id'], 'Cancelled', "Participant status should be 'Cancelled'");
    }
}
