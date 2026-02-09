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

declare(strict_types = 1);

namespace Civi\RemoteParticipant\Event;

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Class UpdateEvent
 *
 * @package Civi\RemoteParticipant\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteParticipant.update API call, so the various stages can be applied
 */
class UpdateEvent extends ChangingEvent {
  public const NAME = 'civi.remoteevent.registration.update';

  /**
   * @var array holds the current participant data  */
  protected $participant = NULL;

  /**
   * @var array holds the current contact data  */
  protected $contact = NULL;

  /**
   * @var array the participant update  */
  protected $participant_update = [];

  /**
   * @var array the contact update  */
  protected $contact_update = [];

  public function __construct(array $submission_data, ?array $event = NULL) {
    parent::__construct($submission_data, $event);
    $this->token_usages = ['update', 'invite'];
  }

  /**
   * Set the current participant
   *
   * @param array $participant_data
   */
  public function setParticipant($participant_data) {
    if ($this->participant === NULL) {
      $this->participant = $participant_data;
      if ($this->participant_id && $this->participant_id != $participant_data['id']) {
        \Civi::log()->debug('UpdateEvent: Participant ID overruled');
      }
      $this->participant_id = $participant_data['id'];
    }
    else {
      $this->addError($this->localise('Participant already loaded.'));
    }
  }

  /**
   * Set the current participant
   *
   * @param array $contact_data
   */
  public function setContact($contact_data) {
    if ($this->contact === NULL) {
      $this->contact = $contact_data;
      if ($this->contact_id && $this->contact_id != $contact_data['id']) {
        \Civi::log()->debug('UpdateEvent: Contact ID overruled');
      }
      $this->contact_id = (int) $contact_data['id'];
    }
    else {
      $this->addError($this->localise('Contact already loaded.'));
    }
  }

  /**
   * Get the participant object
   *
   * @return array
   *   participant data
   */
  public function getParticipant() {
    return $this->participant;
  }

  /**
   * Get the participant data by REFERENCE
   *   for inline editing
   *
   * @return array
   *   participant data
   */
  public function &getParticipantData() {
    return $this->participant;
  }

  /**
   * Set the participant object
   *
   * @return array
   *   participant data
   */
  public function getContact() {
    return $this->contact;
  }

  /**
   * Get the contact_data object, which is used for
   *   contact identification / creation
   *
   * @return array
   *   contact_data data
   */
  public function &getContactData() {
    return $this->contact;
  }

  /**
   * Get the currently planned updates for
   *  the contact.
   * Feel free to add/modify
   */
  public function &getContactUpdates() {
    return $this->contact_update;
  }

  /**
   * Add a field that should be updated on the contact
   *
   * @param string $field_name
   *   the contact field to schedule for update
   *
   * @param mixed $value
   *   the requested new value
   */
  public function addContactUpdate($field_name, $value) {
    $this->contact_update[$field_name] = $value;
  }

  /**
   * Get the currently planned updates for
   *  the participant.
   * Feel free to add/modify
   */
  public function &getParticipantUpdates() {
    return $this->participant_update;
  }

  /**
   * Add a field that should be updated on the participant
   *
   * @param string $field_name
   *   the participant field to schedule for update
   *
   * @param mixed $value
   *   the requested new value
   */
  public function addParticipantUpdate($field_name, $value) {
    $this->participant_update[$field_name] = $value;
  }

  /**
   * Get a the submitted parameters as a reference
   *  this allows you to adjust the submission parameters, but be careful with this
   *
   * @return array
   *   the submitted parameters
   */
  public function &getSubmissionReference() {
    return $this->submission;
  }

}
