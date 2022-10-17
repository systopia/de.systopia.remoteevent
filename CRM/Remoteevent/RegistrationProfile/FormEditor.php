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

use CRM_Remoteevent_ExtensionUtil as E;
use \Civi\RemoteParticipant\Event\ValidateEvent as ValidateEvent;
use Civi\RemoteParticipant\Event\GetParticipantFormEventBase as GetParticipantFormEventBase;


class CRM_Remoteevent_RegistrationProfile_FormEditor extends CRM_Remoteevent_RegistrationProfile
{
    private array $profile_list;

    public function __construct()
    {
        // TODO check if remoteeventformeditor is installed!
        $this->profile_list = self::get_formeditor_profiles();
    }

    /**
     * @param $name
     *
     * @return mixed|string
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    public function getName($name = null)
    {
        // TODO: Implement getName() method.
        foreach ($this->profile_list as $profile) {
            // if name is null return the first one. Dirty
            if (empty($name)) {
                return $profile;
            }
            if ($profile->get_name() == $name) {
                return $profile;
            }
        }
        throw new CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException("Unknown Profile {$name}");
    }


    /**
     * @param $name
     *
     * @return mixed
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    public function get_profile_name($name)
    {
        return $this->get_profile($name)->name();
    }

    public function getFields($name = null, $locale = null)
    {
        // TODO: Implement getFields() method.
        return $this->get_profile($name)->get_fields($locale);
    }

    public function addDefaultValues(GetParticipantFormEventBase $resultsEvent, $name = null)
    {
        // TODO: Implement addDefaultValues() method.
        // not sure if this is needed!
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // internal helper
    public static function get_formeditor_profiles()
    {
        $profiles = CRM_RemoteEventFormEditor_BAO_RemoteEventFormEditorForm::get_remoteevent_profiles();
        $profile_list = [];
        foreach ($profiles as $profile) {
            $profile_list[] = new CRM_Remoteevent_FormEditorProfile(
                $profile['id'],
                $profile['name'],
                $profile['fields']
            );
        }
        return $profile_list;
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    private function get_profile($name)
    {
        foreach ($this->profile_list as $profile) {
            if ($profile->get_name() == $name) {
                return $profile;
            }
        }
//        throw new CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException(
//            "Invalid Profile Name {$name}. Couldn't find profile"
//        );
    }

}