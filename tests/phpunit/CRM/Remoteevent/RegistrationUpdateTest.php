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
            'has_waitlist' => 0,
            'max_participants' => 1,
        ]);

        // register one contact
        $contactA = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], ['email' => $contactA['email']]);
        $this->assertEmpty($registration1['is_error'], "Registration Failed");

        // test getForm for update
        $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
            'context' => 'update',
            'event_id' => $event['id'],
        ])['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertArrayHasKey('email', $fields, "Should have reported listed field 'email' from profile Standard1");
        $this->assertGreaterThan(5, count($fields['email']), "Field specs for 'email' has too little properties");
        $this->assertTrue(empty($fields['email']['value']), "Field 'email' shouldn't come with a value in an anonymous call");

        // register another contact:
        $contactB = $this->createContact();
        $registration2 = $this->updateRegistration($registration1['participant_id'], ['email' => $contactB['email']]);

        $this->fail("TODO: CHECK VALUES");
    }

}
