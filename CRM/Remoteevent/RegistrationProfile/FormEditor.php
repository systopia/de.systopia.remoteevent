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
    private $profile_list;

    public function __construct()
    {
        // TODO check if remoteeventformeditor is installed!
        $this->get_formeditor_profiles();
    }

    public function getName($name = NULl)
    {
        // TODO: Implement getName() method.
        foreach ($this->profile_list as $profile) {
            // for now we just return the first one.
            // this should be overloaded with a $name argument
            return $profile->get_name();
        }
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

    public function getFields($name = NULL, $locale = null)
    {
        // TODO: Implement getFields() method.
        return $this->get_profile($name)->get_fields($locale);
    }

    public function addDefaultValues(GetParticipantFormEventBase $resultsEvent, $name = NULL)
    {
        // TODO: Implement addDefaultValues() method.
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // internal helper
    private function get_formeditor_profiles()
    {
        $profiles = CRM_RemoteEventFormEditor_BAO_RemoteEventFormEditorForm::get_remoteevent_profiles();
        foreach ($profiles as $profile) {
            $this->profile_list[] = new CRM_Remoteevent_FormEditorProfile($profile['id'], $profile['name'], $profile['fields']);
        }
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
        throw new CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException("Invalid Profile Name {$name}. Couldn't find profile");
    }


}