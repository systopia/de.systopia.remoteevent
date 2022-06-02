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
    protected $xcm_profile = null;

    /**
     * Get the currently available contact_data
     *
     * @return array
     *    contact data data
     */
    public abstract function getContact();

    /**
     * Get the contact_data BY REFERENCE, which is used for
     *   contact identification / creation
     *
     * @return array
     *   contact data
     */
    public abstract function &getContactData();

    /**
     * Get the currently available participant data
     *
     * @return array
     *    participant data
     */
    public abstract function getParticipant();

    /**
     * Get the participant data BY REFERENCE, which is used for
     *   registration creation / updates
     *
     * @return array
     *    participant data
     */
    public abstract function &getParticipantData();

    /**
     * Get the name of the XCM profile to be used
     *   for contact matching/creation
     *
     * If an empty value is returned, the default profile will be used
     *   processed with XCM
     *
     * If the special string 'off' is returned, the XCM will not run in update/cancel contexts
     *
     * @return string|null
     */
    public function getXcmMatchProfile()
    {
        // check if this is an override
        if ($this->xcm_profile) {
            return $this->xcm_profile;
        }

        // get context
        $xcm_context = null;
        if ($this instanceof RegistrationEvent) {
            $xcm_context = 'registration';
        } elseif ($this instanceof UpdateEvent) {
            $xcm_context = 'update';
        } elseif ($this instanceof CancelEvent) {
            $xcm_context = 'cancel';
        }

        // check if there is an event with the specific setting
        $event = $this->getEvent();
        if ($event) {
            // there is an event, see if there's a configured profile
            if ($xcm_context == 'registration' &&
                !empty($event['event_remote_registration.remote_registration_xcm_profile'])) {
                return $event['event_remote_registration.remote_registration_xcm_profile'];
            }
            if ($xcm_context == 'update' &&
                !empty($event['event_remote_registration.remote_registration_update_xcm_profile'])) {
                return $event['event_remote_registration.remote_registration_update_xcm_profile'];
            }
        }

        // if there's no override, return the default settings
        switch ($xcm_context) {
            case 'registration':
                return \Civi::settings()->get('remote_registration_xcm_profile');

            case 'update':
                return \Civi::settings()->get('remote_registration_xcm_profile_update');

            default:
                return null;
        }
    }

    /**
     * Set/override the name of the XCM profile to be used
     *   for this ChangingEvent (registration/update/etc)
     *
     * If an empty value is returned, the default profile will be used
     *   processed with XCM
     *
     * @param string $profile_name
     *   XCM profile name to be used for the XCM call(s).
     *   Warning: no further checks whether the profile is valid will be performed
     *
     * @param bool $override
     *   if this parameter is false, a previously set parameter will NOT be overwritten
     *
     * @return string|null
     *   the previously set profile
     */
    public function setXcmMatchProfile($profile_name, $override = true)
    {
        $current_value = $this->xcm_profile;
        if ($override || !$this->xcm_profile) {
            $this->xcm_profile = $profile_name;
        }
        return $current_value;
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

    /**
     * Get the complete submission
     *
     * @return array
     *   submission data
     */
    public function getSubmission()
    {
        return $this->getQueryParameters();
    }
}
