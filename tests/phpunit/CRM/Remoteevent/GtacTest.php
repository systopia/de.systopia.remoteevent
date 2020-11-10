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
 * Tests the "general terms and conditions" field
 *
 * @group headless
 */
class CRM_Remoteevent_GtacTest extends CRM_Remoteevent_TestBase
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
     * Test event with the GTAC field set
     */
    public function testWithGTAC()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_gtac' => "Grant me one hour on love’s most sacred shores<br/>To clasp the bosom that my soul adores,<br/>Lie heart to heart and merge my soul with yours.",
            ]
        );

        // get the field
        $registration_form = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', ['event_id' => $event['id']]);
        $fields = $registration_form['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertTrue(array_key_exists('gtac', $fields), "Field 'gtac' not in registration form");
        $gtac_field = $fields['gtac'];
        $this->assertEquals('Checkbox', $gtac_field['type'], "GTAC field should be checkbox");
        $this->assertEquals('1', $gtac_field['required'], "GTAC field should be required");
        $this->assertNotEmpty($gtac_field['label'], "GTAC label should be set");
        $this->assertNotEmpty($gtac_field['group_name'], "GTAC group_name should be set");
        $this->assertNotEmpty($gtac_field['group_label'], "GTAC group_label should be set");
        if (empty($gtac_field['prefix']) && empty($gtac_field['suffix'])) {
            $this->fail("GTAC text should be either in prefix or suffix or both");
        }
        if (!empty($gtac_field['prefix'])) {
            $this->assertTrue(in_array($gtac_field['prefix_display'], ['inline', 'dialog']), "GTAC prefix_display should be inline or dialogue");
        }
        if (!empty($gtac_field['suffix'])) {
            $this->assertTrue(in_array($gtac_field['suffix_display'], ['inline', 'dialog']), "GTAC suffix_display should be inline or dialogue");
        }
    }

    // TODO: test validation with GTAC

    /**
     * Test event with no GTAC
     */
    public function testWithoutGTAC()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_gtac' => '',
            ]
        );

        // get the field
        $registration_form = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', ['event_id' => $event['id']]);
        $fields = $registration_form['values'];
        $this->assertGetFormStandardFields($fields, true);
        $this->assertArrayNotHasKey('gtac', $fields, "Field 'gtac' listed in registration form although no gtac provided");
    }
}
