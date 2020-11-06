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
class CRM_Remoteevent_ContactMatchingTest extends CRM_Remoteevent_TestBase
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
     * Test registration without a contact
     *  expected: contact is created
     */
    public function testRegistrationWithoutContact()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'Standard2',
            ]
        );

        // generate some contact data without contact
        $contact_data = $this->createContact();
        civicrm_api3('Contact', 'delete', ['id' => $contact_data['id']]);

        // register contact
        $registration_result = $this->registerRemote(
            $event['id'],
            [
                'prefix_id' => $contact_data['prefix_id'],
                'first_name' => $contact_data['first_name'],
                'last_name' => $contact_data['last_name'],
                'email' => $contact_data['email'],
            ]
        );
        $this->assertNotEmpty($registration_result['participant_id'], "Registration failed.");

        // find + load contact
        $result = $this->traitCallAPISuccess(
            'Contact',
            'get',
            [
                'first_name' => $contact_data['first_name'],
                'last_name' => $contact_data['last_name'],
                'email' => $contact_data['email'],
            ]
        );
        $this->assertEquals(1, $result['count'], "There should now be one contact with this data");
    }

    /**
     * Test registration with an existing contact
     *  expected: contact is updated
     */
    public function testRegistrationContactUpdate()
    {
        // add an update XCM profile
        $update_profile_data = json_decode(file_get_contents(E::path('tests/resources/xcm_profile_testing_update.json')), 1);
        $this->assertNotEmpty($update_profile_data, "Couldn't load update profile data");
        $this->setUpXCMProfile('remoteevent_test_update', $update_profile_data);
        CRM_Xcm_Configuration::flushProfileCache();

        // FIRST TEST: profile NOT enabled:
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'Standard2',
            ]
        );

        // generate some contact data without contact
        $contact = $this->createContact();

        // register contact
        $registration_result = $this->registerRemote(
            $event['id'],
            [
                'first_name' => 'Testinho',
                'prefix_id' => $contact['prefix_id'],
                'last_name' => $contact['last_name'],
                'email' => $contact['email'],
                'remote_contact_id' => $this->getRemoteContactKey($contact['id'])
            ]
        );
        $this->assertNotEmpty($registration_result['participant_id'], "Registration failed.");

        // find + load contact
        $current_contact = $this->traitCallAPISuccess('Contact', 'getsingle', ['id' => $contact['id']]);
        $this->assertNotEquals('Testinho', $current_contact['first_name'], "The name should've not been updated, no update profile set");


        // SECOND TEST: ENABLE THE UPDATE PROFILE
        Civi::settings()->set('remote_registration_xcm_profile_update', 'remoteevent_test_update');

        // generate some contact data without contact
        $contact2 = $this->createContact();

        // register contact
        $registration_result2 = $this->registerRemote(
            $event['id'],
            [
                'first_name' => 'Testinho',
                'prefix_id' => $contact2['prefix_id'],
                'last_name' => $contact2['last_name'],
                'email' => $contact2['email'],
                'remote_contact_id' => $this->getRemoteContactKey($contact2['id'])
            ]
        );
        $this->assertNotEmpty($registration_result2['participant_id'], "Registration failed.");

        // find + load contact
        $current_contact = $this->traitCallAPISuccess('Contact', 'getsingle', ['id' => $contact2['id']]);
        $this->assertEquals('Testinho', $current_contact['first_name'], "The name should've not been updated, no update profile set");

    }

}
