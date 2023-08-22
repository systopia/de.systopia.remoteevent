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

use Symfony\Contracts\EventDispatcher\Event;

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

    /**
     * @var  \Civi\RemoteEvent\Event\EventProfile[]
     */
    protected array $profiles = [];

    /**
     * @return \Civi\RemoteEvent\Event\EventProfile[]
     */
    public function getProfiles(): array
    {
        return $this->profiles;
    }

    /**
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    public function getProfileInstance(string $name): \CRM_Remoteevent_RegistrationProfile
    {
        return $this->getProfile($name)->getInstance();
    }

    /**
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    public function getProfile(string $name): \Civi\RemoteEvent\Event\EventProfile
    {
        if (isset($this->profiles[$name])) {
          return $this->profiles[$name];
        }

        throw new \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException("Profile {$name} is not available");
    }

    /**
     * @param string $class_name
     *   FQCN that extends CRM_Remoteevent_RegistrationProfile.
     * @param string $name
     *   Unique profile name. Must be the same as returned by getName() in
     *   profile class.
     * @param callable|null $new_instance_callback
     *   Callback to create a new profile instance. If not specified the class
     *   constructor is used.
     */
    public function addProfile(string $class_name, string $name, string $label, $new_instance_callback = NULL): void
    {
        if (isset($this->profiles[$name])) {
            \Civi::log()->error(sprintf('A profile named "%s" is already registered.', $name));
        } else {
           $profile_data = new \Civi\RemoteEvent\Event\EventProfile($class_name, $name, $label, $new_instance_callback);
           $this->profiles[$name] = $profile_data;
        }
    }

}