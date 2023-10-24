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
class CRM_Remoteevent_RegistrationTest extends CRM_Remoteevent_TestBase
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
     * Test registration with a waiting list
     */
    public function testRegistrationWithoutWaitlist()
    {
        // create an event
        $event = $this->createRemoteEvent([
            'event_remote_registration.remote_registration_default_profile' => 'Standard1',
            'has_waitlist' => 0,
            'max_participants' => 1,
        ]);

        // register one contact
        $contactA = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], ['email' => $contactA['email']]);
        $this->assertEmpty($registration1['is_error'], "First Registration Failed");

        // register another contact:
        $contactB = $this->createContact();
        $registration2 = $this->registerRemote($event['id'], ['email' => $contactB['email']]);
        $this->assertNotEmpty($registration2['is_error'],
                              "Second Validation should have failed, max_participants exceeded.");
        $this->assertArraySubset(['Event is booked out'], $registration2['errors'], "The reason should have been 'Event booked out'");
    }

    /**
     * Test registration with a waiting list
     */
    public function testRegistrationWithWaitlist()
    {
        // create an event
        $event = $this->createRemoteEvent([
              'event_remote_registration.remote_registration_default_profile' => 'Standard1',
              'has_waitlist' => 1,
              'max_participants' => 1,
          ]);

        // register one contact
        $contactA = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], ['email' => $contactA['email']]);
        $this->assertEmpty($registration1['is_error'], "First Registration Failed");

        // Retrieve the event.
        $eventRetrieved = $this->getRemoteEvent($event['id']);
        $this->assertNotEmpty($eventRetrieved['has_active_waitlist'], 'The flag "has_active_waitlist" should have the value "1".');

        // register another contact:
        $contactB = $this->createContact();
        $registration2 = $this->registerRemote($event['id'], ['email' => $contactB['email']]);
        $this->assertArraySubset(['You have been added to the waitlist.'], $registration2['status'], "The status should have been 'You have been added to the waitlist'");
    }

    /**
     * Test the registration_suspended flag
     */
    public function testRegistrationSuspended()
    {
        // check if disabled by default
        $event1 = $this->createRemoteEvent();
        $this->assertTrue(empty($event1['event_remote_registration.remote_registration_suspended']), "Registration shouldn't be suspended by default");
        $this->assertNotEmpty($event1['can_register'], "Registration should be possible");

        // check if it works when created
        $event2 = $this->createRemoteEvent(
            ['event_remote_registration.remote_registration_suspended' => 1]
        );
        $this->assertNotEmpty($event2['event_remote_registration.remote_registration_suspended'], "RegistrationSuspended not saved.");
        $this->assertEmpty($event2['can_register'], "Registration should NOT be possible (suspended)");

        // check if it works when added later
        $event3 = $this->createRemoteEvent();
        $this->assertNotEmpty($event3['can_register'], "Registration should be possible");
        // set suspended flag
        $set_suspended = ['id' => $event3['id'], 'event_remote_registration.remote_registration_suspended' => 1];
        CRM_Remotetools_CustomData::resolveCustomFields($set_suspended);
        $this->traitCallAPISuccess('Event', 'create', $set_suspended);
        // verify result
        $event3 = $this->getRemoteEvent($event3['id']);
        $this->assertEmpty($event3['can_register'], "Registration should NOT be possible");
    }

    /**
     * Test field length validation
     */
    public function testMaxFieldLength()
    {
        // create an event
        $event = $this->createRemoteEvent(['event_remote_registration.remote_registration_default_profile' => 'Standard2']);

        // test registering contact
        $contact1 = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], [
            'profile'    => 'Standard2',
            'prefix_id'  => $contact1['prefix_id'],
            'first_name' => $contact1['first_name'],
            'last_name'  => $contact1['last_name'],
            'email'      => $contact1['email'],
        ]);
        $this->assertEmpty($registration1['is_error'], "Registration failed event without field length violation");

        // test registering contact
        $contact2 = $this->createContact([]);
        $this->callAPIFailure('RemoteParticipant', 'create', [
            'event_id'     => $event['id'],
            'profile'      => 'Standard2',
            'prefix_id'    => $contact2['prefix_id'],
            'formal_title' => 'aasdgdfga gdf ofdsijgs[dosdfgsdfgdff ijgfsdpgdfg sdfhg hoigjsdfogds',
            'first_name'   => 'ajdsofijdfoiahsdfioushepigauwfhepiufhpwaiuefhpawiuefhpwaeiufhsadasd',
            'last_name'    => 'adfsdgksngkfn dfondfobindfsokm dkdf ofdsijgs[dof ijgfsdpoigjsdfogds',
            'email'        => $contact2['email'],
        ], "Value too long");
    }

}
