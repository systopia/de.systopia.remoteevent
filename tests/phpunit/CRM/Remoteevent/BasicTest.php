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
 * Some very basic tests around CiviRemote Event
 *
 * @group headless
 */
class CRM_Remoteevent_BasicTest extends CRM_Remoteevent_TestBase
{
    /**
     * Some very basic RemoteEvent.get tests
     */
    public function testRemoteEventGet()
    {
        // create an event
        $event = $this->createRemoteEvent([
            'title' => "Supertestevent",
            'event_remote_registration.remote_registration_default_profile' => 'Standard2',
            'event_remote_registration.remote_registration_profiles'        => ['Standard2'],
        ]);

        // get the API
        $remote_event = $this->traitCallAPISuccess('RemoteEvent', 'getsingle', ['id' => $event['id']]);

        // do some basic comparison
        $params_to_compare = ['id', 'title', 'start_date', 'event_type_id', 'is_active'];
        foreach ($params_to_compare as $param) {
            $this->assertEquals($event[$param], $remote_event[$param], "Parameter {$param} differs");
        }

        // get the registration form and see if the fields are there
        $registration_form = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', ['event_id' => $event['id']]);
        $fields = $registration_form['values'];
        $this->assertGetFormStandardFields($fields, true);
        $expected_fields = ['email', 'prefix_id', 'formal_title', 'last_name'];
        foreach ($expected_fields as $expected_field) {
            $this->assertTrue(array_key_exists($expected_field, $fields), "Field {$expected_field} not in registration form");
        }
    }

    /**
     * RemoteEvent.get with a
     */
    public function testRemoteEventGetWithToken()
    {
        // create an event
        $event = $this->createRemoteEvent([
              'title' => "Supertestevent",
              'event_remote_registration.remote_registration_default_profile' => 'OneClick',
          ]);
        $other_event = $this->createRemoteEvent([
              'title' => "Super-other-event",
              'event_remote_registration.remote_registration_default_profile' => 'OneClick',
          ]);

        // make sure they're both there
        $result = $this->traitCallAPISuccess('RemoteEvent','get', []);
        $this->assertEquals(2, $result['count'], "There should be two events");

        // create an invited contact
        $contact = $this->createContact();
        $result = $this->traitCallAPISuccess(
            'Participant',
            'create',
            [
                'event_id' => $event['id'],
                'contact_id' => $contact['id'],
                'status_id' => $this->getParticipantInvitedStatus(),
                'role_id' => 'Attendee'
            ]
        );
        $participant_id = $result['id'];
        $this->assertParticipantStatus($participant_id, 'Invited', "Participant status should be 'Invited'");
        $token = CRM_Remotetools_SecureToken::generateEntityToken('Participant', $participant_id, null, 'invite');

        // now try RemoteEvent.get/single with the token
        $result = $this->traitCallAPISuccess(
            'RemoteEvent',
            'get',
            ['token' => $token]);
        $this->assertEquals(1, $result['count'], "This should only return the event linked by token");
        $this->assertEquals($event['id'], $result['id'], "This should only return the event linked by token");
        $result = $this->traitCallAPISuccess(
            'RemoteEvent',
            'getsingle',
            ['token' => $token]);
    }
}
