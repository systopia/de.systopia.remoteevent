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


namespace Civi\RemoteParticipant\Event;
use Civi\RemoteEvent;

/**
 * Class ChangingEvent
 *
 * @package Civi\RemoteParticipant\Event
 *
 * This is an event type that will actually perform changes to the DB,
 *  e.g. RemoteParticipant.create, RemoteParticipant.update, RemoteParticipant.cancel
 */
abstract class ChangingEvent extends RemoteEvent
{
    protected $contact_was_updated = false;
    protected $participant_was_updated = false;
    protected $xcm_match_profile = 'setting:remote_registration_xcm_profile';
    protected $xcm_update_profile = 'setting:remote_registration_xcm_profile_update';

    /**
     * Get the name of the XCM profile to be used
     *   for contact matching/creation
     *
     * If an empty value is returned, the default profile will be used
     *   processed with XCM
     *
     * @return string|null
     */
    public function getXcmMatchProfile()
    {
        // empty value -> use default
        if (empty($this->xcm_match_profile)) {
            return null;
        }

        // settings prefix -> fetch from settings
        if (substr($this->xcm_match_profile, 0, 8) == 'setting:') {
            return \Civi::settings()->get(substr($this->xcm_match_profile, 8));
        }

        // otherwise, we'll assume this is the profile name
        return $this->xcm_match_profile;
    }

    /**
     * Get the name of the XCM profile to be used
     *   for contact updates
     *
     * If an empty value is submitted, the default profile
     *   will be used
     *
     * If the value is prefixed with 'setting:' the profile
     *   will be read from the CiviCRM settings
     *
     * @param string|null $profile_name
     *   the new profile name or an empty value to disable XCM
     */
    public function setXcmMatchProfile($profile_name)
    {
        $this->xcm_match_profile = $profile_name;
    }


    /**
     * Get the name of the XCM profile to be used
     *   for contact updates
     *
     * If an empty value is returned, the update should not be
     *   processed with XCM
     *
     * @return string|null
     */
    public function getXcmUpdateProfile()
    {
        // empty value -> disabled
        if (empty($this->xcm_update_profile)) {
            return null;
        }

        // settings prefix -> fetch from settings
        if (substr($this->xcm_update_profile, 0, 8) == 'setting:') {
            return \Civi::settings()->get(substr($this->xcm_update_profile, 8));
        }

        // otherwise, we'll assume this is the profile name
        return $this->xcm_update_profile;
    }

    /**
     * Get the name of the XCM profile to be used
     *   for contact updates
     *
     * If an empty value is submitted, the update should not be
     *   processed with XCM
     *
     * If the value is prefixed with 'setting:' the profile
     *   will be read from the CiviCRM settings
     *
     * @param string|null $profile_name
     *   the new profile name or an empty value to disable XCM
     */
    public function setXcmUpdateProfile($profile_name)
    {
        $this->xcm_update_profile = $profile_name;
    }

    /**
     * Was the contact already updated?
     *
     * @return boolean
     *   was it?
     */
    public function isContactUpdated()
    {
        return $this->contact_was_updated;
    }

    /**
     * Mark the contact as updated.
     *
     * This should prevent any processes down the line
     *   to upgrade again.
     */
    public function setContactUpdated()
    {
        $this->contact_was_updated = true;
    }

    /**
     * Was the participant already updated?
     *
     * @return boolean
     *   was it?
     */
    public function isParticipantUpdated()
    {
        return $this->participant_was_updated;
    }

    /**
     * Mark the participant as updated
     *
     * This should prevent any processes down the line
     *   to upgrade again.
     */
    public function setParticipantUpdated()
    {
        $this->participant_was_updated = true;
    }

}
