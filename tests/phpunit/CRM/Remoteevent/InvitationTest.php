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
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_Remoteevent_InvitationTest extends CRM_Remoteevent_TestBase
{
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
                'event_remote_registration.remote_registration_profiles' => ['Standard2', 'OneClick'],
            ]
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
                'role_id' => 'Attendee'
            ]
        );
        $participant_id = $result['id'];
        $this->assertParticipantStatus($participant_id, 'Invited', "Participant status should be 'Invited'");

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'profile' => 'OneClick',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 1
            ]
        );
        $this->assertParticipantStatus($participant_id, 'Registered', "Participant status should be 'Registered'");


        // NOW do the same with DECLINED
        $contact2 = $this->createContact();
        $result = $this->traitCallAPISuccess(
            'Participant',
            'create',
            [
                'event_id' => $event['id'],
                'contact_id' => $contact2['id'],
                'status_id' => $this->getParticipantInvitedStatus(),
                'role_id' => 'Attendee'
            ]
        );
        $participant2_id = $result['id'];
        $this->assertParticipantStatus($participant2_id, 'Invited', "Participant status should be 'Invited'");

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant2_id, null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'profile' => 'OneClick',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 0
            ]
        );
        $this->assertParticipantStatus($participant2_id, 'Rejected', "Participant status should be 'Cancelled'");
    }

    /**
     * Test not invited (without participant) with OneClick form.
     *
     * Expected results:
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
                'event_remote_registration.remote_registration_profiles' => ['Standard2', 'OneClick'],
            ]
        );

        // create invite participant
        $contact = $this->createContact();

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Contact', $contact['id'], null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'event_id' => $event['id'],
                'profile' => 'OneClick',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        //$this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 1
            ]
        );
        $participant = $this->traitCallAPISuccess(
            'Participant',
            'getsingle',
            [
                'event_id' => $event['id'],
                'contact_id' => $contact['id'],
            ]
        );
        $this->assertParticipantStatus($participant['id'], 'Registered', "Participant status should be 'Registered'");


        // NOW do the same with DECLINED
        $contact2 = $this->createContact();
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Contact', $contact2['id'], null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'event_id' => $event['id'],
                'profile' => 'OneClick',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        //$this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 0
            ]
        );
        $participant2 = $this->traitCallAPISuccess(
            'Participant',
            'getsingle',
            [
                'event_id' => $event['id'],
                'contact_id' => $contact2['id'],
            ]
        );
        $this->assertParticipantStatus($participant2['id'], 'Cancelled', "Participant status should be 'Cancelled'");
    }

    /**
     * Test invited (with participant) with Standard2 form.
     *
     * Expected results:
     *  - participant is in status 'Invited'
     *  - get_form has field option: accept/decline invitation
     *  - "accept" -> participant in status 'Registered'
     *  - "declined" -> participant in status 'Cancelled'
     */
    public function testInvitedParticipantStandard2()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles' => ['Standard2', 'OneClick'],
            ]
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
                'role_id' => 'Attendee'
            ]
        );
        $participant_id = $result['id'];
        $this->assertParticipantStatus($participant_id, 'Invited', "Participant status should be 'Invited'");

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'invite');

        // check Standard2 Profile
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'profile' => 'Standard2',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // check if the prefill worked
        foreach (['first_name', 'last_name', 'email'] as $field) {
            $this->assertEquals(
                $contact[$field],
                $fields[$field]['value'],
                "Prefill for field '{$field}' did not work"
            );
        }

        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 1,
                'first_name' => $contact['first_name'],
                'last_name' => $contact['last_name'],
                'email' => $contact['email'],
            ]
        );
        $this->assertParticipantStatus($participant_id, 'Registered', "Participant status should be 'Registered'");


        // NOW do the same with DECLINED
        $contact2 = $this->createContact();
        $result = $this->traitCallAPISuccess(
            'Participant',
            'create',
            [
                'event_id' => $event['id'],
                'contact_id' => $contact2['id'],
                'status_id' => $this->getParticipantInvitedStatus(),
                'role_id' => 'Attendee'
            ]
        );
        $participant2_id = $result['id'];
        $this->assertParticipantStatus($participant2_id, 'Invited', "Participant status should be 'Invited'");

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant2_id, null, 'invite');

        // check Standard2 Profile
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'profile' => 'Standard2',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 0,
                'first_name' => $contact['first_name'],
                'last_name' => $contact['last_name'],
                'email' => $contact['email'],
            ]
        );
        $this->assertParticipantStatus($participant2_id, 'Rejected', "Participant status should be 'Cancelled'");
    }

    /**
     * Test not invited (without participant) with Standard2 form.
     *
     * Expected results:
     *  - get_form has field option: accept/decline invitation
     *  - "accept" -> participant in status 'Registered'
     *  - "declined" -> participant in status 'Cancelled'
     */
    public function testInvitedNoParticipantStandard2()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles' => ['Standard2', 'OneClick'],
            ]
        );

        // create invite participant
        $contact = $this->createContact();

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Contact', $contact['id'], null, 'invite');

        // check OneClick Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'event_id' => $event['id'],
                'profile' => 'Standard2',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        //$this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // check if the prefill worked
        foreach (['first_name', 'last_name', 'email'] as $field) {
            $this->assertEquals(
                $contact[$field],
                $fields[$field]['value'],
                "Prefill for field '{$field}' did not work"
            );
        }


        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 1
            ]
        );
        $participant = $this->traitCallAPISuccess(
            'Participant',
            'getsingle',
            [
                'event_id' => $event['id'],
                'contact_id' => $contact['id'],
            ]
        );
        $this->assertParticipantStatus($participant['id'], 'Registered', "Participant status should be 'Registered'");


        // NOW do the same with DECLINED
        $contact2 = $this->createContact();
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Contact', $contact2['id'], null, 'invite');

        // check Standard2 Profile: it should have a field 'confirmation'
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'event_id' => $event['id'],
                'profile' => 'Standard2',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        //$this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 0,
                'first_name' => $contact['first_name'],
                'last_name' => $contact['last_name'],
                'email' => $contact['email'],
            ]
        );
        $participant2 = $this->traitCallAPISuccess(
            'Participant',
            'getsingle',
            [
                'event_id' => $event['id'],
                'contact_id' => $contact2['id'],
            ]
        );
        $this->assertParticipantStatus($participant2['id'], 'Cancelled', "Participant status should be 'Cancelled'");
    }


    /**
     * Test invited (with participant) with Standard2 form
     *   with TOKEN and remote_contact_id
     *
     * Expected results:
     *  - participant is in status 'Invited'
     *  - get_form has field option: accept/decline invitation
     *  - "accept" -> participant in status 'Registered'
     *  - "declined" -> participant in status 'Cancelled'
     */
    public function testInvitedParticipantStandard2TokenAndRemoteID()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles' => ['Standard2', 'OneClick'],
            ]
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
                'role_id' => 'Attendee'
            ]
        );
        $participant_id = $result['id'];
        $this->assertParticipantStatus($participant_id, 'Invited', "Participant status should be 'Invited'");

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'invite');

        // check Standard2 Profile
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'profile' => 'Standard2',
                'remote_contact_id' => $this->getRemoteContactKey($contact['id']),
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // check if the prefill worked
        foreach (['first_name', 'last_name', 'email'] as $field) {
            $this->assertEquals(
                $contact[$field],
                $fields[$field]['value'],
                "Prefill for field '{$field}' did not work"
            );
        }

        // CONFIRM the registration
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 1,
                'first_name' => $contact['first_name'],
                'last_name' => $contact['last_name'],
                'email' => $contact['email'],
            ]
        );
        $this->assertParticipantStatus($participant_id, 'Registered', "Participant status should be 'Registered'");
    }


    /**
     * Test invited (with participant) with OneClick form
     *   with waiting list and event full
     *
     * Expected results:
     *  - participant is in status 'Invited'
     *  - event has waiting list
     *  - event is full
     */
    public function testInvitedParticipantOneClickWaitingList()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles' => ['Standard2', 'OneClick'],
                'has_waitlist' => 1,
                'max_participants' => 1,
            ]
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
                'role_id' => 'Attendee'
            ]
        );
        $participant_id = $result['id'];
        $this->assertParticipantStatus($participant_id, 'Invited', "Participant status should be 'Invited'");

        // register another participant, so the event is full
        $other_contact = $this->createContact();
        $this->registerRemote($event['id'],
            [
                'remote_contact_id' => $this->getRemoteContactKey($other_contact['id']),
            ]
        );

        // now try to confirm the invitation
        $invitation_token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'invite');
        $this->registerRemote(
            $event['id'],
            [
                'token' => $invitation_token,
                'confirm' => 1
            ]
        );
        $this->assertParticipantStatus($participant_id, 'On waitlist', "Participant status should be 'Registered'");
    }

    /**
     * Test invited (with participant) with Standard2 form.
     *
     * Expected results:
     *  - participant is in status 'Invited'
     *  - get_form has field option: accept/decline invitation
     *  - "accept" -> participant in status 'Registered'
     *  - "declined" -> participant in status 'Cancelled'
     */
    public function testDeclineInvitedParticipantStandard2WithValidation()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'Standard2',
                'event_remote_registration.remote_registration_profiles' => ['Standard2'],
            ]
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
                'role_id' => 'Attendee'
            ]
        );
        $participant_id = $result['id'];
        $this->assertParticipantStatus($participant_id, 'Invited', "Participant status should be 'Invited'");

        // generate token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'invite');

        // check Standard2 Profile
        $fields = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'get_form',
            [
                'profile' => 'Standard2',
                'token' => $token,
            ]
        )['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('confirm', $fields), "Field 'confirm' not in registration form");

        // check if the prefill worked
        foreach (['first_name', 'last_name', 'email'] as $field) {
            $this->assertEquals(
                $contact[$field],
                $fields[$field]['value'],
                "Prefill for field '{$field}' did not work"
            );
        }

        // DECLINE the invitation without any further data
        // first: RemoteParticipant.validate
        $validation_errors = $this->traitCallAPISuccess(
            'RemoteParticipant',
            'validate',
            [
                'event_id' => $event['id'],
                'profile' => 'Standard2',
                'token' => $token,
                'confirm' => 0,
            ]
        )['values'];
        $this->assertEmpty($validation_errors, "There should not be validation errors for confirm=0.");

        // then: RemoteParticipant.create
        $this->registerRemote(
            $event['id'],
            [
                'token' => $token,
                'confirm' => 0,
            ]
        );
        $this->assertParticipantStatus($participant_id, 'Rejected', "Participant status should be 'Rejected'");
    }


}
