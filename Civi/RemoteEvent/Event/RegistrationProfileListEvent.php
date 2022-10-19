<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2022 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


namespace Civi\RemoteEvent\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class RegistrationProfileListEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event compiles a list of Registration Profiles for Remote Events
 */
class RegistrationProfileListEvent extends Event
{

    const NAME = 'civi.remoteevent.registration.profile.list';

    protected $profiles; // array[CRM_Remoteevent_EventProfile]

    protected int $id_counter;

    public function __construct()
    {
        $this->id_counter = 0;
    }

    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    public function getProfileInstance($name)
    {
        foreach ($this->profiles as $profile) {
            if ($profile->get_unique_id() == $name) {
                return $profile->getInstance($name);
            }
        }
        throw new \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException(
            "Profile '{$name}' isn't available."
        );
    }

    /**
     * @param $name
     *
     * @return \CRM_Remoteevent_EventProfile
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    public function getProfile($name): \CRM_Remoteevent_EventProfile
    {
        foreach ($this->profiles as $profile) {
            if ($profile->getProfileName() == $name) {
                return $profile;
            }
        }
        throw new \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException("Profile {$name} is not available");
    }

    /**
     * @param $classname
     * @param $profile_name
     * @param $params
     *
     * @return void
     */
    public function addProfile($classname, $profile_name, $internal_id, $id_prefix = null, $params = null)
    {
        $profile_data = new \CRM_Remoteevent_EventProfile($classname, $profile_name, $internal_id, ++$this->id_counter, $id_prefix, $params);
        $this->profiles[] = $profile_data;
    }

}