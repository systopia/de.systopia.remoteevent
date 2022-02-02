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
 * Test event functions with an anonymous contact
 *
 * @group headless
 */
class CRM_RemoteEvent_IssuesTest extends CRM_Remoteevent_TestBase
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
     * Test to reproduce issue 17: state_province_id not working
     *
     * @see https://github.com/systopia/de.systopia.remoteevent/issues/17
     */
    public function testIssue17_StateProvinceId()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'Standard3',
                'event_remote_registration.remote_registration_profiles' => ['Standard3'],
            ]
        );

        // try to register with one ClickProfile
        $contact = $this->createContact();
        $result = $this->registerRemote(
            $event['id'],
            [
                'first_name' => $contact['first_name'],
                'last_name' => $contact['last_name'],
                'email' => $contact['email'],
                'prefix_id' => 1,
                'country_id' => '1082',
                'state_province_id' => '1082-2245'
            ]
        );
        if (!empty($result['is_error'])) {
            $this->fail("State/Province ID cleanup failed: " . $result['error_message']);
        }
    }
}
