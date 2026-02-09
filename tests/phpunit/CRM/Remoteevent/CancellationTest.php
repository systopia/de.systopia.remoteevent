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
 * Tests regarding the RemoteParticipant.get_form API
 *
 * @group headless
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_Remoteevent_CancellationTest extends CRM_Remoteevent_TestBase
{
    /**
     * Test RemoteParticipant.cancel API anonymously
     */
    public function testCancelAnonymously()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'allow_selfcancelxfer' => 1,
            ]
        );

        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);

        // test cancel form without ID
        try {
            civicrm_api3(
                'RemoteParticipant',
                'cancel',
                [
                    'event_id' => $event['id'],
                    'email' => $contact['email']
                ]
            );
            $this->fail("RemoteParticipant.cancel without identification should fail");
        } catch (CRM_Core_Exception $ex) {
            // todo: check error message?
             $error_message = $ex->getMessage();
             $this->assertMatchesRegularExpression('/no participants found/i', $error_message, "This seems to be the wrong kind of exception");
        }
    }

    /**
     * Test RemoteParticipant.cancel API with remote_contact_id
     */
    public function testCancelViaRemoteID()
    {
        // create an event
        $event = $this->createRemoteEvent([
                'allow_selfcancelxfer' => 1,
        ]);

        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $participant_id = $this->traitCallAPISuccess('Participant', 'getvalue', [
            'contact_id' => $contact['id'],
            'event_id'   => $event['id'],
            'return'     => 'id']);

        // cancel
        $this->traitCallAPISuccess('RemoteParticipant', 'cancel', [
            'event_id' => $event['id'],
            'remote_contact_id' => $this->getRemoteContactKey($contact['id'])
        ]);

        // verify contact is cancelled
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', ['id' => $participant_id]);
        $this->assertParticipantStatus($participant['participant_id'], 'Cancelled', "Participant doesn't seem to be cancelled");
    }

    /**
     * Test RemoteParticipant.cancel with token
     */
    public function testCancelViaToken()
    {
        // create an event
        $event = $this->createRemoteEvent([
          'allow_selfcancelxfer' => 1,
        ]);

        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $participant_id = $this->traitCallAPISuccess('Participant', 'getvalue', [
            'contact_id' => $contact['id'],
            'event_id'   => $event['id'],
            'return'     => 'id']);

        // cancel via token
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'cancel');
        $this->traitCallAPISuccess('RemoteParticipant', 'cancel', [
            'token' => $token
        ]);

        // verify contact is cancelled
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', ['id' => $participant_id]);
        $this->assertParticipantStatus($participant['participant_id'], 'Cancelled', "Participant doesn't seem to be cancelled");
    }

    /**
     * Test RemoteParticipant.cancel API with remote_contact_id and token
     */
    public function testCancelViaRemoteIdAndToken()
    {
        // create an event
        $event = $this->createRemoteEvent([
              'allow_selfcancelxfer' => 1,
          ]);

        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $participant_id = $this->traitCallAPISuccess('Participant', 'getvalue', [
            'contact_id' => $contact['id'],
            'event_id'   => $event['id'],
            'return'     => 'id']);

        // cancel
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'cancel');
        $this->traitCallAPISuccess('RemoteParticipant', 'cancel', [
            'event_id' => $event['id'],
            'remote_contact_id' => $this->getRemoteContactKey($contact['id']),
            'token' => $token
        ]);

        // verify contact is cancelled
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', ['id' => $participant_id]);
        $this->assertParticipantStatus($participant['participant_id'], 'Cancelled', "Participant doesn't seem to be cancelled");
    }

    /**
     * Test RemoteParticipant.cancel API with remote_contact_id
     */
    public function testCancelWithinAllowedTime()
    {
        // create an event
        $event = $this->createRemoteEvent([
              'allow_selfcancelxfer' => 1,
              'selfcancelxfer_time'  => 24,
              'start_date'           => date('Y-m-d H:i:s', strtotime("now + 48 hours")),
          ]);

        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $participant_id = $this->traitCallAPISuccess('Participant', 'getvalue', [
            'contact_id' => $contact['id'],
            'event_id'   => $event['id'],
            'return'     => 'id']);

        // cancel
        $this->traitCallAPISuccess('RemoteParticipant', 'cancel', [
            'event_id' => $event['id'],
            'remote_contact_id' => $this->getRemoteContactKey($contact['id'])
        ]);

        // verify contact is cancelled
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', ['id' => $participant_id]);
        $this->assertParticipantStatus($participant['participant_id'], 'Cancelled', "Participant doesn't seem to be cancelled");
    }

    /**
     * Test RemoteParticipant.cancel API with remote_contact_id
     */
    public function testCancelAfterAllowedTime()
    {
        // create an event
        $event = $this->createRemoteEvent([
              'allow_selfcancelxfer' => 1,
              'selfcancelxfer_time'  => 49,
              'start_date'           => date('Y-m-d H:i:s', strtotime("now + 48 hours")),
          ]);

        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $participant_id = $this->traitCallAPISuccess('Participant', 'getvalue', [
            'contact_id' => $contact['id'],
            'event_id'   => $event['id'],
            'return'     => 'id']);

        // cancel
        try {
            $response = civicrm_api3('RemoteParticipant', 'cancel', [
                'event_id' => $event['id'],
                'remote_contact_id' => $this->getRemoteContactKey($contact['id'])
            ]);
            $this->fail("Cancelling a participant after the selfcancelxfer_time limit should not succeed.");
        } catch (CRM_Core_Exception $ex) {
            // didn't work: verify it's for the right reason
            $this->assertNotEmpty(strstr($ex->getMessage(), 'does not allow cancellation less than'), "There should be an error message regarding the cancellation time restrictions");
        }

        // verify contact NOT cancelled
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', ['id' => $participant_id]);
        $this->assertParticipantStatus($participant['participant_id'], 'Registered', "Participant doesn't seem to be cancelled");
    }

}
