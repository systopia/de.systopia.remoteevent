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
class CRM_Remoteevent_SessionTest extends CRM_Remoteevent_TestBase
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
    public function testSimpleRegistration()
    {
        // create an event
        $event = $this->createRemoteEvent([]);
        $session1 = $this->createEventSession($event['id']);
        $session2 = $this->createEventSession($event['id']);

        // register one contact
        $contactA = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], [
            'email'                    => $contactA['email'],
            "session{$session1['id']}" => 1,
            "session{$session2['id']}" => 0,
        ]);
        $this->assertEmpty($registration1['is_error'], "Registration Failed");
        $registered_session_ids = CRM_Remoteevent_BAO_Session::getParticipantRegistrations($registration1['participant_id']);
        $this->assertTrue(in_array($session1['id'], $registered_session_ids), "Participant should be registered for session 1");
        $this->assertFalse(in_array($session2['id'], $registered_session_ids), "Participant should NOT be registered for session 2");
    }
}
