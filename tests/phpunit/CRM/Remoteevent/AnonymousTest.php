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
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_RemoteEvent_AnonymousTest_InvitationTest extends CRM_Remoteevent_TestBase
{
    /**
     * Test invited (with participant) with OneClick form.
     *
     * Expected results:
     *  - participant rejected
     */
    public function testRegisterAnonymousParticipantOneClick()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles' => ['Standard2', 'OneClick'],
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
            ]
        );
        $this->assertTrue((bool) $result['is_error'], "You shouldn't be able to register anonymously.");
    }


    /**
     * Test invited (with participant) with OneClick form.
     *
     * Expected results:
     *  - participant rejected
     */
    public function testRegisterAnonymousParticipantOneStandard2()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles'        => ['Standard2', 'OneClick'],
            ]
        );

        // try to register with one ClickProfile
        $contact = $this->createContact();
        $result = $this->registerRemote($event['id'], [
            'profile'    => 'Standard2',
            'prefix_id'  => $contact['prefix_id'],
            'first_name' => $contact['first_name'],
            'last_name'  => $contact['last_name'],
            'email'      => $contact['email'],
        ]);
        $this->assertFalse((bool) $result['is_error'], "You should be able to register anonymously with the Standard2 profile.");
    }



    /**
     * Test invited (with participant) with OneClick form.
     *
     * Expected results:
     *  - participant rejected
     */
    public function testRegisterNewContactParticipantOneClick()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles' => ['Standard2', 'OneClick'],
            ]
        );

        // try to register with one ClickProfile
        $contact = $this->createContact();
        // delete contact so it's going to be a new one
        civicrm_api3('Contact', 'delete', ['id' => $contact['id']]);

        $result = $this->registerRemote(
            $event['id'],
            [
                'first_name' => $contact['first_name'],
                'last_name' => $contact['last_name'],
                'email' => $contact['email'],
            ]
        );
        $this->assertTrue((bool) $result['is_error'], "You shouldn't be able to register anonymously.");
    }


    /**
     * Test invited (with participant) with OneClick form.
     *
     * Expected results:
     *  - participant rejected
     */
    public function testRegisterNewContactParticipantOneStandard2()
    {
        // create an event
        $event = $this->createRemoteEvent(
            [
                'event_remote_registration.remote_registration_default_profile' => 'OneClick',
                'event_remote_registration.remote_registration_profiles'        => ['Standard2', 'OneClick'],
            ]
        );

        // try to register with one ClickProfile
        $contact = $this->createContact();
        // delete contact so it's going to be a new one
        civicrm_api3('Contact', 'delete', ['id' => $contact['id']]);

        // then try and register with the values
        $result = $this->registerRemote($event['id'], [
            'profile'    => 'Standard2',
            'prefix_id'  => $contact['prefix_id'],
            'first_name' => $contact['first_name'],
            'last_name'  => $contact['last_name'],
            'email'      => $contact['email'],
        ]);
        $this->assertFalse((bool) $result['is_error'], "You should be able to register anonymously with the Standard2 profile.");
    }
}
