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

/**
 * Registration profile Class for all Registration Profiles. Needs an *internal* id to instanciate
 */
class CRM_Remoteevent_RegistrationProfile_FormEditor extends CRM_Remoteevent_RegistrationProfile
{
    /**
     * @var array
     */
    private array $profile_list;

    /**
     * @var int
     */
    private int $default_id;

    /**
     * default id is the internal formeditor ID
     *
     * @param $default_id
     */
    public function __construct($default_id)
    {
        // TODO check if remoteeventformeditor is installed!
        $this->profile_list = self::get_formeditor_profiles();
        $this->default_id = $default_id;
    }

    /**
     * @param $name
     *
     * @return mixed|string
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    public function getName()
    {
        return $this->get_profile()->getName();
    }

    /**
     * @param $locale
     *
     * @return array
     * @throws \CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException
     */
    public function getFields($locale = null): array
    {
        return $this->get_profile()->getFields($locale);
    }

    /**
     * @param \Civi\RemoteParticipant\Event\GetParticipantFormEventBase $resultsEvent
     * @param $name
     *
     * @return void
     */
    public function addDefaultValues(GetParticipantFormEventBase $resultsEvent, $name = null)
    {
        // TODO: Implement addDefaultValues() method.
        // not sure if this is needed!
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // internal helper

    /**
     * Get all Form Editor profiles. Array of CRM_Remoteevent_FormEditorProfile
     * @return array
     */
    public static function get_formeditor_profiles(): array
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
    private function get_profile() : CRM_Remoteevent_FormEditorProfile
    {
        foreach ($this->profile_list as $profile) {
            if ($profile->get_id() == $this->default_id) {
                return $profile;
            }
        }
        throw new CRM_Remoteevent_Exceptions_RegistrationProfileNotFoundException(
            "Invalid Profile Name {$name}. Couldn't find profile"
        );
    }

}