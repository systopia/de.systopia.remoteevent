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
        $this->assertTrue((boolean) strstr($registration2['errors']['event_id'], 'Event is booked out'),
                          "The reason should have been 'Event booked out'");

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

        // register another contact:
        $contactB = $this->createContact();
        $registration2 = $this->registerRemote($event['id'], ['email' => $contactB['email']]);
        $this->assertEmpty($registration1['is_error'], "Second Registration Failed, despite waitlist");


    }
}
