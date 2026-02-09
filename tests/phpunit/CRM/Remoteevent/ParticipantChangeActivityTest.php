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
 * Tests for the recording of change activities
 *
 * @group headless
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_Remoteevent_ParticipantChangeActivityTest extends CRM_Remoteevent_TestBase
{
    /**
     * Test a simple participant update,
     *  with update recording enabled
     */
    public function testSimpleUpdate()
    {
        // enable activity tracking (with activity_type_id 1)
        Civi::settings()->set('remote_participant_change_activity_type_id', 1);

        // create an event
        $event = $this->createRemoteEvent([]);
        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $participant = $this->traitCallAPISuccess('Participant', 'getsingle', [
            'contact_id' => $contact['id'],
            'event_id'   => $event['id']
        ]);

        // there should NOT be an activity with that contact, because it's a new one
        $change_activities = civicrm_api3('Activity', 'get', [
            'target_id'        => $contact['id'],
            'activity_type_id' => 1,
        ]);
        $this->assertEmpty($change_activities['values'], "There should NOT be an activity with that contact, because it's a new one");

        // NOW update the participant
        $participant = $this->traitCallAPISuccess('Participant', 'create', [
            'id' => $participant['id'],
            'status_id' => 'Cancelled',
        ]);

        // THIS time there should be an activity with that contact, because it's a new one
        $change_activities = civicrm_api3('Activity', 'get', [
            'target_id'        => $contact['id'],
            'activity_type_id' => 1,
        ]);
        $this->assertEquals(1, $change_activities['count'], "There should be a change activity with that contact");
        $change_activity = reset($change_activities['values']);
        // look for the jumbled (md5) names instead of the real ones
        $this->assertNotEmpty(strstr($change_activity['details'], md5('Registered')), "The activity should mention the Registered->Cancelled change");
        $this->assertNotEmpty(strstr($change_activity['details'], md5('Cancelled')), "The activity should mention the Registered->Cancelled change");
    }

    /**
     * Test a simple participant update,
     *  with update recording DISABLED
     */
    public function testSimpleUpdateDisabled()
    {
        // disable activity tracking (with activity_type_id 0)
        Civi::settings()->set('remote_participant_change_activity_type_id', 0);

        // create an event
        $event = $this->createRemoteEvent([]);
        // register one participant
        $contact = $this->createContact();
        $this->registerRemote($event['id'], ['email' => $contact['email']]);
        $participant = $this->traitCallAPISuccess(
            'Participant',
            'getsingle',
            [
                'contact_id' => $contact['id'],
                'event_id' => $event['id']
            ]
        );

        // there should NOT be an activity with that contact, because it's a new one
        $change_activities = civicrm_api3(
            'Activity',
            'get',
            [
                'target_id' => $contact['id'],
                'activity_type_id' => 1,
            ]
        );
        $this->assertEmpty(
            $change_activities['values'],
            "There should NOT be an activity with that contact, because it's a new one"
        );

        // NOW update the participant
        $participant = $this->traitCallAPISuccess(
            'Participant',
            'create',
            [
                'id' => $participant['id'],
                'status_id' => 'Cancelled',
            ]
        );

        // THIS time there should be an activity with that contact, because it's a new one
        $change_activities = civicrm_api3(
            'Activity',
            'get',
            [
                'target_id' => $contact['id'],
                'activity_type_id' => 1,
            ]
        );
        $this->assertEmpty(
            $change_activities['values'],
            "There should NOT be an activity with that contact, the update tracking is disabled"
        );
    }

    /**
     * Test a simple participant update,
     *  with custom fields
     */
    public function testCustomFieldUpdate()
    {
        // enable activity tracking (with activity_type_id 1)
        Civi::settings()->set('remote_participant_change_activity_type_id', 1);

        // create custom fields
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('tests/resources/option_group_age_range.json'));
        $customData->syncCustomGroup(E::path('tests/resources/custom_group_participant_test1.json'));

        // create an event
        $event = $this->createRemoteEvent([]);
        // register one participant
        $contact = $this->createContact();
        $participant_data = [
            'contact_id'                           => $contact['id'],
            'event_id'                             => $event['id'],
            'participant_test1.event_age_range'    => 3,
            'participant_test1.event_comm_twitter' => 'Twitter',
        ];
        CRM_Remoteevent_CustomData::resolveCustomFields($participant_data);
        $participant = $this->traitCallAPISuccess('Participant', 'create', $participant_data);

        // there should NOT be an activity with that contact, because it's a new one
        $change_activities = civicrm_api3('Activity', 'get', [
            'target_id'        => $contact['id'],
            'activity_type_id' => 1,
        ]);
        $this->assertEmpty($change_activities['values'], "There should NOT be an activity with that contact, because it's a new one");

        // NOW update the participant
        $participant_update = [
            'id'                                   => $participant['id'],
            'participant_test1.event_age_range'    => 4,
            'participant_test1.event_comm_twitter' => 'Twatter',
        ];
        CRM_Remoteevent_CustomData::resolveCustomFields($participant_update);
        $participant = $this->traitCallAPISuccess('Participant', 'create', $participant_update);

        // THIS time there should be an activity with that contact, because it's a new one
        $change_activities = civicrm_api3('Activity', 'get', [
            'target_id'        => $contact['id'],
            'activity_type_id' => 1,
        ]);
        // todo: this needs a post hook
        $this->assertEquals(1, $change_activities['count'], "There should be a change activity with that contact");
        $change_activity = reset($change_activities['values']);
        $this->assertNotEmpty(strstr($change_activity['details'], 'Twatter'), "The activity should mention the Twitter->Twatter change");
        $this->assertNotEmpty(strstr($change_activity['details'], '40-49'), "The activity should mention the 30-39 to 40-49 change");
    }
}
