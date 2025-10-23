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
 */
class CRM_Remoteevent_UpdateTest extends CRM_Remoteevent_TestBase
{
    /**
     * Test RemoteParticipant.get_form context=create API anonymously
     */
    public function testUpdateAnonymously()
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
            $form_spec = civicrm_api3(
                'RemoteParticipant',
                'update',
                [
                    'event_id' => $event['id'],
                    'email' => $contact['email']
                ]
            );
            $this->fail("RemoteParticipant.cancel without identification should fail");
        } catch (CRM_Core_Exception $ex) {
            // todo: check error message?
            // $error_message = $ex->getMessage();
            // $this->assertMatchesRegularExpression('/invalid/', $error_message, "This seems to be the wrong kind of exception");
        }
    }

    /**
     * Test RemoteParticipant.get_form context=create API anonymously
     */
    public function testUpdateViaRemoteID()
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
        $this->traitCallAPISuccess('RemoteParticipant', 'update', [
            'event_id' => $event['id'],
            'remote_contact_id' => $this->getRemoteContactKey($contact['id'])
        ]);

        // verify contact is cancelled
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', ['id' => $participant_id]);
        $this->assertEquals('Cancelled', $participant['participant_status'], "Participant doesn't seem to be cancelled");
    }

    /**
     * Test RemoteParticipant.get_form context=create API anonymously
     */
    public function testUpdateViaToken()
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
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'update');
        $this->traitCallAPISuccess('RemoteParticipant', 'update', [
            'token' => $token
        ]);

        // verify contact is cancelled
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', ['id' => $participant_id]);
        $this->assertEquals('Cancelled', $participant['participant_status'], "Participant doesn't seem to be cancelled");
    }

}
