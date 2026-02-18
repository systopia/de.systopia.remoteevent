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

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Form controller for event location settings
 */
class CRM_Remoteevent_Form_EventLocation extends CRM_Event_Form_ManageEvent {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('alternativelocation');
  }

  public function buildQuickForm() {
    $event_location_type = CRM_Remoteevent_EventLocation::singleton($this->_id)->getEventContactType();

    $this->addEntityRef(
        'event_alternativelocation_contact_id',
        E::ts('Event Location'),
        [
          'entity' => 'Contact',
          'api' => [
            'params' => [
              'contact_type' => 'Organization',
              'contact_sub_type' => $event_location_type['name'],
            ],
          ],
        ],
        FALSE
    );

    $this->add(
        'wysiwyg',
        'event_alternativelocation_remark',
        E::ts('Additional Information for this Event'),
        [],
        FALSE
    );

    $this->addButtons(
        [
            [
              'type'      => 'submit',
              'name'      => E::ts('Add Session'),
              'isDefault' => TRUE,
            ],
        ]
    );

    // prepare some variables
    $current_values = civicrm_api3('Event', 'getsingle', ['id' => $this->_id]);
    CRM_Remoteevent_CustomData::labelCustomFields($current_values);
    $prefix = 'event_alternative_location.';
    $contact_field = CRM_Remoteevent_CustomData::getCustomFieldKey(
      'event_alternative_location',
      'event_alternativelocation_contact_id'
    );
    $defaults = [
      'event_alternativelocation_contact_id' => (int) $current_values["{$contact_field}_id"] ?? NULL,
      'event_alternativelocation_remark' =>
      $current_values["{$prefix}event_alternativelocation_remark"] ?? NULL,
    ];
    $this->setDefaults($defaults);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    // store values
    $event_update = [
      'id' => $this->_id,
      'is_template'
      => CRM_Remoteevent_RemoteEvent::isTemplate($this->_id),
      'event_alternative_location.event_alternativelocation_contact_id'
      => $values['event_alternativelocation_contact_id'] ?? NULL,
      'event_alternative_location.event_alternativelocation_remark'
      => $values['event_alternativelocation_remark'] ?? NULL,
    ];
    CRM_Remoteevent_CustomData::resolveCustomFields($event_update);
    civicrm_api3('Event', 'create', $event_update);

    $this->_action = CRM_Core_Action::UPDATE;
    parent::endPostProcess();
  }

}
