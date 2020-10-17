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
 * Tests regarding registration flags
 *
 * @group headless
 */
class CRM_Remoteevent_EventFlagsTest extends CRM_Remoteevent_TestBase
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
     * Test flags anonymously
     */
    public function testFlagsAnonymous()
    {
        // create an event
        $event = $this->createRemoteEvent([
            'allow_selfcancelxfer' => 0,
        ]);

        // check the flags for a newly created event
        $this->checkFlagFormat('registration_count', '/^[0-9]+$/', $event);
        $this->assertEquals('0', $event['registration_count'], 'The "flag" registration_count should be zero for a new event.');

        $this->checkFlagFormat('participant_registration_count', '/^[0-9]+$/', $event);
        $this->assertEquals('0', $event['participant_registration_count'], 'The "flag" participant_registration_count should always be zero for an anonymous query.');

        $this->checkFlagFormat('is_registered', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['is_registered'], 'The flag can_register should always be 0');

        $this->checkFlagFormat('can_register', '/^(0|1)$/', $event);
        $this->assertEquals('1', $event['can_register'], 'The flag can_register should be 1 for a new event.');

        $this->checkFlagFormat('can_instant_register', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_instant_register'], 'The flag can_instant_register should be 0 for an event without OneClick profile.');

        $this->checkFlagFormat('can_edit_registration', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_edit_registration'], 'The flag can_edit_registration should be 0 this event.');

        $this->checkFlagFormat('can_cancel_registration', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_cancel_registration'], 'The flag can_cancel_registration should be 0 this event.');


        // add a registration and try again
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $event = $this->getRemoteEvent($event['id']);

        $this->checkFlagFormat('registration_count', '/^[0-9]+$/', $event);
        $this->assertEquals('1', $event['registration_count'], 'The "flag" registration_count should be 1 with a registration.');

        $this->checkFlagFormat('participant_registration_count', '/^[0-9]+$/', $event);
        $this->assertEquals('0', $event['participant_registration_count'], 'The "flag" participant_registration_count should always be zero for an anonymous query.');

        $this->checkFlagFormat('is_registered', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['is_registered'], 'The flag can_register should always be 0');

        $this->checkFlagFormat('can_register', '/^(0|1)$/', $event);
        $this->assertEquals('1', $event['can_register'], 'The flag can_register should be 1 for a new event.');

        $this->checkFlagFormat('can_instant_register', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_instant_register'], 'The flag can_instant_register should be 0 for an event without OneClick profile.');

        $this->checkFlagFormat('can_edit_registration', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_edit_registration'], 'The flag can_edit_registration should be 0 this event.');

        $this->checkFlagFormat('can_cancel_registration', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_cancel_registration'], 'The flag can_cancel_registration should be 0 this event.');



        // todo: turn on instant registration
        // todo: turn on allow_selfcancelxfer
        // todo: turn on registration window
    }

    /**
     * Test flags anonymously
     */
    public function testFlagsPersonalised()
    {
        // create an event
        $event = $this->createRemoteEvent([
          'allow_selfcancelxfer' => 0,
        ]);

        // create a remote contact
        $contact = $this->createContact();
        $remote_key = $this->getRemoteContactKey($contact['id']);

        // reload event with personalised data
        $event = $this->getRemoteEvent($event['id'], ['remote_contact_id' => $remote_key]);

        // check the flags for a newly created event
        $this->checkFlagFormat('registration_count', '/^[0-9]+$/', $event);
        $this->assertEquals('0', $event['registration_count'], 'The "flag" registration_count should be zero for a new event.');

        $this->checkFlagFormat('participant_registration_count', '/^[0-9]+$/', $event);
        $this->assertEquals('0', $event['participant_registration_count'], 'The "flag" participant_registration_count should always be zero for a new event.');

        $this->checkFlagFormat('is_registered', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['is_registered'], 'The flag can_register should be 0 for a new event');

        $this->checkFlagFormat('can_register', '/^(0|1)$/', $event);
        $this->assertEquals('1', $event['can_register'], 'The flag can_register should be 1 for a new event.');

        $this->checkFlagFormat('can_instant_register', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_instant_register'], 'The flag can_instant_register should be 0 for an event without OneClick profile.');

        $this->checkFlagFormat('can_edit_registration', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_edit_registration'], 'The flag can_edit_registration should be 0 this event.');

        $this->checkFlagFormat('can_cancel_registration', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_cancel_registration'], 'The flag can_cancel_registration should be 0 this event.');


        // add a registration and try again
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $event = $this->getRemoteEvent($event['id'], ['remote_contact_id' => $remote_key]);

        // check the flags for a newly created event
        $this->checkFlagFormat('registration_count', '/^[0-9]+$/', $event);
        $this->assertEquals('1', $event['registration_count'], 'The registration_count should be 1 now.');

        $this->checkFlagFormat('participant_registration_count', '/^[0-9]+$/', $event);
        $this->assertEquals('1', $event['participant_registration_count'], 'The participant_registration_count should be 1 now.');

        $this->checkFlagFormat('is_registered', '/^(0|1)$/', $event);
        $this->assertEquals('1', $event['is_registered'], 'The flag can_register should be 1 when registered');

        $this->checkFlagFormat('can_register', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_register'], 'The flag can_register should be 0, because we already registered');

        $this->checkFlagFormat('can_instant_register', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_instant_register'], 'The flag can_register should be 0, because we already registered');

        $this->checkFlagFormat('can_edit_registration', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_edit_registration'], 'The flag can_edit_registration should be 0 this event.');

        $this->checkFlagFormat('can_cancel_registration', '/^(0|1)$/', $event);
        $this->assertEquals('0', $event['can_cancel_registration'], 'The flag can_cancel_registration should be 0 this event.');
    }

    /**
     * Verify the flag format / value
     *
     * @param string $flag_name
     * @param string $pattern
     * @param array $data
     */
    protected function checkFlagFormat($flag_name, $pattern, $data)
    {
        $this->assertArrayHasKey($flag_name, $data, "The flag {$flag_name} should always be there.");
        $this->assertRegExp($pattern, $data[$flag_name], "The flag {$flag_name} has in invalid value: '{$data[$flag_name]}'");
    }
}
