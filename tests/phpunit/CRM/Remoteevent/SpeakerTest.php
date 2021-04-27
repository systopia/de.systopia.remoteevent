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
 * Tests regarding speaker delivery on RemoteEvent.get
 *
 * @group headless
 */
class CRM_Remoteevent_SpeakerTest extends CRM_Remoteevent_TestBase
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
     * Test to check behaviour with disabled speakers
     */
    public function testSpeakersDisabled()
    {
        // disable speakers
        $this->setSpeakerRoles(null);

        // create an event
        $event = $this->createRemoteEvent();

        // register one contact
        $contactA = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], ['email' => $contactA['email']]);
        $this->assertEmpty($registration1['is_error'], "Registration Failed");

        // get the event data
        $remote_event = $this->getRemoteEvent($event['id']);
        $this->assertTrue(!isset($remote_event['speakers']),
                                    "When speakers are disabled, the 'speakers' key should not be there.");
    }

    /**
     * Test to check behaviour with enabled speakers
     */
    public function testSpeakersEnabled()
    {
        // disable speakers
        $this->setSpeakerRoles([2]);

        // create an event
        $event = $this->createRemoteEvent();

        // register one contact
        $contactA = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], ['email' => $contactA['email']]);
        $this->assertEmpty($registration1['is_error'], "Registration Failed");

        // get the event data
        $remote_event = $this->getRemoteEvent($event['id']);
        $this->assertArrayHasKey('speakers', $remote_event,
                                    "When speakers are enabled, the 'speakers' key should be there");
        $this->assertEquals('[]', $remote_event['speakers'],
                                 "Speakers should be (json) empty");
    }

    /**
     * Test to check behaviour with enabled speakers
     */
    public function testSpeakers()
    {
        // disable speakers
        $this->setSpeakerRoles([4]);

        // create an event
        $event = $this->createRemoteEvent();

        // register one normal contact
        $contact1 = $this->createContact();
        $registration1 = $this->registerRemote($event['id'], [
            'email'   => $contact1['email'],
            'role_id' => 1
        ]);
        $this->assertEmpty($registration1['is_error'], "Registration Failed");

        // register speaker only contact
        $contact2 = $this->createContact();
        $registration2 = $this->registerRemote($event['id'], [
            'email'   => $contact2['email'],
            'role_id' => 4
        ]);
        $this->assertEmpty($registration2['is_error'], "Registration Failed");

        // register mixed contact
        $contact3 = $this->createContact();
        $registration3 = $this->registerRemote($event['id'], [
            'email'   => $contact3['email'],
            'role_id' => [1,4]
        ]);
        $this->assertEmpty($registration3['is_error'], "Registration Failed");

        // get the results
        $remote_event = $this->getRemoteEvent($event['id']);
        $this->assertArrayHasKey('speakers', $remote_event,
                                 "When speakers are enabled, the 'speakers' key should be there");
        $event_speakers = json_decode($remote_event['speakers'], true);
        $this->assertNotNull($event_speakers, "Couldn't decode speakers json");

        // check speakers and get speaker IDs
        $speaker_ids = [];
        foreach ($event_speakers as $event_speaker_fields) {
            $event_speaker = $this->mapFieldArray($event_speaker_fields);
            $this->assertNotEmpty($event_speaker['name'], 'Speaker should have a name');
            $this->assertNotEmpty($event_speaker['first_name'], 'Speaker should have a first_name');
            $this->assertNotEmpty($event_speaker['last_name'], 'Speaker should have a last_name');
            $this->assertNotEmpty($event_speaker['roles'], 'Speaker should have at least one role');
            $this->assertNotEmpty($event_speaker['contact_id'], 'Speaker contact_id should be set.');
            $speaker_ids[] = $event_speaker['contact_id'];
        }

        // contact 1 should not be in the speakers list
        $this->assertFalse(in_array($contact1['id'], $speaker_ids), 'Contact1 should not be in the speaker list');

        // contact 2 _should_ be in the speakers list
        $this->assertTrue(in_array($contact2['id'], $speaker_ids), 'Contact2 should be in the speaker list');

        // contact 3 _should_ be in the speakers list
        $this->assertTrue(in_array($contact3['id'], $speaker_ids), 'Contact3 should be in the speaker list');
    }

    /**
     * Test to check behaviour with many events and many speakers
     */
    public function testManySpeakersWithManyEvents()
    {
        $roles = CRM_Remoteevent_EventCache::getRoles();
        $events = $this->createRemoteEvents(5);
        $contacts = $this->createContacts(10);

        foreach ($contacts as $contact) {
            // randomly pick events to register to
            $event_list = $this->randomSubset($events);
            foreach ($event_list as $event) {
                $registration = $this->registerRemote($event['id'], [
                    'email'   => $contact['email'],
                    'role_id' => $this->randomSubset($roles),
                ]);
                $this->assertEmpty($registration['is_error'], "Registration Failed");
            }
        }

        // now run 3 test rounds with different speaker roles set
        foreach (range(1,3) as $round) {
            $speaker_roles = $this->randomSubset(array_keys($roles));
            $this->setSpeakerRoles($speaker_roles);
            $speaker_role_labels = [];
            foreach ($speaker_roles as $speaker_role_id) {
                $speaker_role_labels[] = $roles[$speaker_role_id];
            }
            $remote_events = $this->getRemoteEvents(array_keys($events));
            foreach ($remote_events as $remote_event) {
                $event_speakers = json_decode($remote_event['speakers'], true);
                $this->assertNotNull($event_speakers, "Couldn't decode speakers json");
                foreach ($event_speakers as $event_speaker_spec) {
                    $event_speaker = $this->mapFieldArray($event_speaker_spec);
                    $event_speaker_roles = explode(',', $event_speaker['roles']);
                    foreach ($event_speaker_roles as $event_speaker_role) {
                        $event_speaker_role = trim($event_speaker_role);
                        $this->assertTrue(in_array($event_speaker_role, $speaker_role_labels), "The role given here should be one of the speaker roles");
                    }
                }
            }
        }
    }
}
