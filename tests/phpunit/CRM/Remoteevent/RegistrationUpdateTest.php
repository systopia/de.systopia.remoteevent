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
 * Tests regarding event registration updates
 *
 * @group headless
 */
class CRM_Remoteevent_RegistrationUpdateTest extends CRM_Remoteevent_TestBase
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
     * Test simple upgrade
     */
    public function testSimpleRegistrationUpdate()
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
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'token'    => $token,
            'context'  => 'update',
            'event_id' => $event['id'],
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertArrayHasKey('email', $fields, "Should have reported listed field 'email' from profile Standard1");
        $this->assertGreaterThan(5, count($fields['email']), "Field specs for 'email' has too little properties");

        // test registration update
        $different_last_name = $this->createContact()['last_name'];

        // first validate
        $validation = $this->traitCallAPISuccess('RemoteParticipant', 'validate', [
               'token'      => $token,
               'context'    => 'update',
               'event_id'   => $event['id'],
               'email'      => $contactA_before['email'],
               'prefix_id'  => $contactA_before['prefix_id'],
               'first_name' => $contactA_before['first_name'],
               'last_name'  => $different_last_name
           ]);


        // then: update
        $registration2 = $this->updateRegistration([
            'token'      => $token,
            'email'      => $contactA_before['email'],
            'prefix_id'  => $contactA_before['prefix_id'],
            'first_name' => $contactA_before['first_name'],
            'last_name'  => $different_last_name
        ]);
        $this->assertEquals($registration1['participant_id'], $registration2['participant_id'], "The registration's participant ID has changed.");
        $contactA_after = $fields = $this->traitCallAPISuccess('Contact', 'getsingle', ['id' => $contactA_before['id']]);
        $this->assertEquals($different_last_name, $contactA_after['last_name'], "Last name was not updated!");
    }
}
