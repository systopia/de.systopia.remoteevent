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
class CRM_Remoteevent_GetFormTest extends CRM_Remoteevent_TestBase
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
     * Test RemoteParticipant.get_form action=create API anonymously
     */
    public function testCreateAnonymous()
    {
        // create an event
        $event = $this->createRemoteEvent([
              'event_remote_registration.remote_registration_default_profile' => 'Standard1',
              'event_remote_registration.remote_registration_profiles'        => ['Standard2','Standard1','OneClick'],
        ]);

        // check default profile
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'event_id' => $event['id'],
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertArrayHasKey('email', $fields, "Should have reported listed field 'email' from profile Standard1");
        $this->assertGreaterThan(5, count($fields['email']), "Field specs for 'email' has too little properties");
        $this->assertTrue(empty($fields['email']['value']), "Field 'email' shouldn't come with a value in an anonymous call");

        // check Standard1 profile
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'event_id' => $event['id'],
            'profile'  => 'Standard1'
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertArrayHasKey('email', $fields, "Should have reported listed field 'email' from profile Standard1");
        $this->assertGreaterThan(5, count($fields['email']), "Field specs for 'email' has too little properties");
        $this->assertTrue(empty($fields['email']['value']), "Field 'email' shouldn't come with a value in an anonymous call");

        // check OneClick Profile
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'event_id' => $event['id'],
            'profile'  => 'OneClick'
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertEmpty($fields, "OneClick Profile should not list any fields");

        // check illegal profile
        try {
            civicrm_api3('RemoteParticipant', 'get_form', [
                'event_id' => $event['id'],
                'profile'  => 'Standard3'
            ]);
            $this->fail("RemoteParticipant.get_form with an illegal profile should cause an exception");
        } catch (CiviCRM_API3_Exception $ex) {
            $error_message = $ex->getMessage();
            $this->assertRegExp('/cannot be used/', $error_message, "This seems to be the wrong kind of exception");
        }
    }

    /**
     * Test RemoteParticipant.get_form action=cancel API anonymously
     */
    public function testCancel()
    {
        // create an event
        $event = $this->createRemoteEvent([
              'event_remote_registration.remote_registration_default_profile' => 'Standard1',
              'event_remote_registration.remote_registration_profiles'        => ['Standard2','Standard1','OneClick'],
        ]);

        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);

        // check cancellation with default profile
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'action'   => 'cancel',
            'event_id' => $event['id'],
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);

        // todo: i don't know what we're expecting here, I don't think cancellation has any fields

    }

}
