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
 * Tests regarding greetings on the registration form
 *
 * @group headless
 */
class CRM_Remoteevent_GreetingsTest extends CRM_Remoteevent_TestBase
{
    /**
     * Test simple upgrade
     */
    public function testRegistrationUpdateGreeting()
    {
        // create an event
        $event = $this->createRemoteEvent([
              'event_remote_registration.remote_registration_default_profile'        => 'Standard2',
              'event_remote_registration.remote_registration_profiles'               => ['Standard2'],
              'event_remote_registration.remote_registration_default_update_profile' => 'Standard2',
              'event_remote_registration.remote_registration_update_profiles'        => ['Standard2'],
              'has_waitlist' => 0,
              'max_participants' => 1,
          ]);

        // register one contact
        $contactA_before = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], [
            'email'      => $contactA_before['email'],
            'prefix_id'  => $contactA_before['prefix_id'],
            'first_name' => $contactA_before['first_name'],
            'last_name'  => $contactA_before['last_name'],
        ]);
        $this->assertEmpty($registration1['is_error'], "Registration Failed");

        // test getForm for update
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $registration1['participant_id'], null, 'update');
        $reply = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'token'    => $token,
            'context'  => 'update',
            'event_id' => $event['id'],
        ]);

        // now there should be at least one status_message with the last_name in it
        $this->assertArrayHasKey('status_messages', $reply, "There should be a greeting status message in the reply.");
        $message_found = false;
        foreach ($reply['status_messages'] as $status_message) {
            $message_found |= (bool) strstr($status_message['message'], $contactA_before['first_name']);
        }
        $this->assertTrue((bool) $message_found, "There should be a greeting status message containing the first name in the reply.");
    }

}
