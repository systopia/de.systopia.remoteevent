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
     * Test as filters
     */
    public function testFlagFilters(): void
    {
        // create events
        $event1 = $this->createRemoteEvent();
        $this->createRemoteEvent();
        $event3 = $this->createRemoteEvent();
        $my_event = ['id' => $event1['id']];

        // create a remote contact
        $contact = $this->createContact();
        $remote_key = $this->getRemoteContactKey($contact['id']);
        $my_contact = ['remote_contact_id' => $remote_key];
        $my_event_contact = $my_event + $my_contact;

        $this->callAPISuccessGetCount('RemoteEvent', $my_event, 1);
        $this->callAPISuccessGetCount('RemoteEvent', $my_contact, 3);
        $this->callAPISuccessGetCount('RemoteEvent', ['id' => $event3['id'] + 1], 0);

        // let's see what happens when we add limits
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['option.limit' => 3], 3);
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['option.limit' => 2], 2);
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['option.limit' => 1], 1);

        // there should not be any registered contact yet
        $this->callAPISuccessGetCount('RemoteEvent',$my_event_contact + ['is_registered' => 0], 1);
        $this->callAPISuccessGetCount('RemoteEvent',$my_event_contact + ['is_registered' => 1], 0);
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['is_registered' => 0], 3);
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['is_registered' => 1], 0);

        // now, let's register, and see if something changes
        $this->registerRemote($event1['id'], ['email' => $contact['email']]);
        $this->callAPISuccessGetCount('RemoteEvent', $my_event_contact, 1);
        $this->callAPISuccessGetCount('RemoteEvent',$my_event_contact + ['is_registered' => 0], 0);
        $this->callAPISuccessGetCount('RemoteEvent',$my_event_contact + ['is_registered' => 1], 1);
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['is_registered' => 0], 2);
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['is_registered' => 1], 1);

        // let's see what happens when we add limits + flags
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['is_registered' => 0, 'option.limit' => 1], 1);
        $this->callAPISuccessGetCount('RemoteEvent',$my_contact + ['is_registered' => 1, 'option.limit' => 3], 1);
    }

    /**
     * Test flag performance improvements
     */
    public function testFlagFilterOptimisation()
    {
        // create 50 events
        $events = [];
        foreach (range(0, 50) as $index) {
            $events[] = $this->createRemoteEvent();
        }

        // register one contact for the last event
        $last_event = end($events);
        $contact = $this->createContact();
        $remote_key = $this->getRemoteContactKey($contact['id']);
        $this->registerRemote($last_event['id'], ['email' => $contact['email']]);

        // find all events registered the contact is registered to (without performance improvements)
        Civi::settings()->set('remote_event_get_performance_enhancement', false);
        $timestamp = microtime(true);
        $registered_events = $this->findRemoteEvents(['is_registered' => 1, 'remote_contact_id' => $remote_key]);
        $runtime_without_boost = microtime(true) - $timestamp;
        $this->assertEquals(1, $registered_events['count'], "There should be exactly one event we're registered to");

        // find all events registered the contact is registered to (with performance improvements)
        Civi::settings()->set('remote_event_get_performance_enhancement', true);
        $timestamp = microtime(true);
        $registered_events = $this->findRemoteEvents(['is_registered' => 1, 'remote_contact_id' => $remote_key]);
        $runtime_with_boost = microtime(true) - $timestamp - 0.1; // 0.1s buffer
        $this->assertEquals(1, $registered_events['count'], "There should be exactly one event we're registered to");

        // make sure the boost actually improves performance
        $this->assertGreaterThan($runtime_with_boost, $runtime_without_boost, "The runtime boost doesn't seem to improve the runtime.");
    }

    /**
     * Test flag performance improvements using the offset value
     */
    public function testFlagFilterOptimisationWithOffset()
    {
        // create 50 events
        $EVENT_COUNT = 50;
        $events = [];
        foreach (range(0, $EVENT_COUNT-1) as $index) {
            $new_event = $this->createRemoteEvent();
            $events[$new_event['id']] = $new_event;
        }

        // register contacts for random subset of the events
        foreach ([false, true] as $performance_enhancement) {
            $timestamp = microtime(true);
            // enable/disable performance enhancement
            Civi::settings()->set('remote_event_get_performance_enhancement', $performance_enhancement);

            foreach ([10, 25, 49] as $limit) {
                // get a random subset of events
                $random_event_ids = array_rand($events, $limit);
                sort($random_event_ids);

                // create a new contact, and register to them
                $contact = $this->createContact();
                $remote_key = $this->getRemoteContactKey($contact['id']);
                foreach ($random_event_ids as $event_id) {
                    $this->registerRemote($event_id, ['email' => $contact['email']]);
                }

                // now get all the events the contact is registered to
                $registered_event_ids = [];
                $offset = 0;
                foreach (range(0, ($EVENT_COUNT / $limit) + 1) as $iteration) {
                    $registered_events = $this->findRemoteEvents([
                        'is_registered' => 1,
                        'remote_contact_id' => $remote_key,
                        'option.limit' => $limit,
                        'option.offset' => $offset]);
                    foreach ($registered_events['values'] as $event) {
                        $registered_event_ids[] = $event['id'];
                    }
                    $offset += $limit;
                }

                // and finally compare
                $registered_event_ids = array_unique($registered_event_ids);
                sort($registered_event_ids);
                $this->assertEquals($random_event_ids, $registered_event_ids, "Registered events incorrect [performance-enhancement: {$performance_enhancement}, offset {$offset}, limit {$limit}]");
            }
            $runtime = (int) (microtime(true) - $timestamp);
            print_r("Runtime " . ($performance_enhancement ? "with" : "without") . " performance enhancement: " . $runtime . "s\n");
        }
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
