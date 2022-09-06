<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2022 SYSTOPIA                            |
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
 * Tests around event templating and instances
 *
 * @group headless
 */
class CRM_Remoteevent_TemplateTest extends CRM_Remoteevent_TestBase
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
     * Test to see if the event_remote_registration data is copied when instantiating a template
     *
     * @see https://github.com/systopia/de.systopia.remoteevent/issues/28
     */
    public function testTemplateInstanceViaApi()
    {
        // create an event
        $event_template = $this->createRemoteEventTemplate([
            'event_remote_registration.remote_registration_enabled' => 1,
            'event_remote_registration.remote_disable_civicrm_registration' => 1,
            'event_remote_registration.remote_use_custom_event_location' => 1,
            'event_remote_registration.remote_registration_gtac' => 'SOME GTAC',
        ]);

        // create instance
        $spawned_from_template = $this->createRemoteEvent(['template_id' => $event_template['id']], true);

        // check if all parameters are there
        $this->assertArrayHasKey('event_remote_registration.remote_registration_gtac', $spawned_from_template, "The template instance should have the gtac");
        $this->assertNotEmpty($spawned_from_template['event_remote_registration.remote_registration_gtac'],"The template instance should have the gtac");
        $this->assertEquals('SOME GTAC', $spawned_from_template['event_remote_registration.remote_registration_gtac'], "The template instance should have the gtac");

        // check remote_registration_enabled
        $this->assertArrayHasKey('remote_registration_enabled', $spawned_from_template, "The template instance should have the field 'remote_registration_enabled'.");
        $this->assertNotEmpty($spawned_from_template['remote_registration_enabled'], "The template instance should have the flag 'remote_registration_enabled' set to true.");

        // check other fields
        foreach (['remote_disable_civicrm_registration', 'remote_use_custom_event_location'] as $field) {
            $field_key = 'event_remote_registration.' . $field;
            $this->assertArrayHasKey($field_key, $spawned_from_template, "The template instance should have the field {$field_key}.");
            $this->assertNotEmpty($spawned_from_template[$field_key], "The template instance should have the flag {$field_key} set to true.");
        }
    }

    /**
     * Test to see if the event_remote_registration data is copied when instantiating a template
     *
     * @see https://github.com/systopia/de.systopia.remoteevent/issues/28
     */
    public function testTemplateInstanceViaBAOCopy()
    {
        // create an event
        $event_template = $this->createRemoteEventTemplate([
           'event_remote_registration.remote_registration_enabled' => 1,
           'event_remote_registration.remote_disable_civicrm_registration' => 1,
           'event_remote_registration.remote_use_custom_event_location' => 1,
           'event_remote_registration.remote_registration_gtac' => 'SOME GTAC',
        ]);

        // create instance
        $event_copy_bao = CRM_Event_BAO_Event::copy($event_template['id']);
        $event_copy_bao->is_template = 0;
        $event_copy_bao->save();
        $spawned_from_template = $this->getRemoteEvent($event_copy_bao->id);

        // check if all parameters are there
        $this->assertArrayHasKey('event_remote_registration.remote_registration_gtac', $spawned_from_template, "The template instance should have the gtac");
        $this->assertNotEmpty($spawned_from_template['event_remote_registration.remote_registration_gtac'],"The template instance should have the gtac");
        $this->assertEquals('SOME GTAC', $spawned_from_template['event_remote_registration.remote_registration_gtac'], "The template instance should have the gtac");

        // check remote_registration_enabled
        $this->assertArrayHasKey('remote_registration_enabled', $spawned_from_template, "The template instance should have the field 'remote_registration_enabled'.");
        $this->assertNotEmpty($spawned_from_template['remote_registration_enabled'], "The template instance should have the flag 'remote_registration_enabled' set to true.");

        // check other fields
        foreach (['remote_disable_civicrm_registration', 'remote_use_custom_event_location'] as $field) {
            $field_key = 'event_remote_registration.' . $field;
            $this->assertArrayHasKey($field_key, $spawned_from_template, "The template instance should have the field {$field_key}.");
            $this->assertNotEmpty($spawned_from_template[$field_key], "The template instance should have the flag {$field_key} set to true.");
        }
    }
}
