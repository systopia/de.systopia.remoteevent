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
use Civi\RemoteParticipant\Event\GetParticipantFormEventBase as GetParticipantFormEventBase;

/**
 * Implements profile 'Standard1': Email only
 */
class CRM_Remoteevent_RegistrationProfile_Standard1 extends CRM_Remoteevent_RegistrationProfile {

  /**
   * Get the internal name of the profile represented
   *
   * @return string name
   */
  public function getName() {
    return 'Standard1';
  }

  /**
   * @param string $locale
   *   the locale to use, defaults to null (current locale)
   *
   * @return array field specs
   * @see CRM_Remoteevent_RegistrationProfile::getFields()
   *
   */
  public function getFields($locale = NULL) {
    $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
    return [
      'contact_base' => [
        'type'        => 'fieldset',
        'name'        => 'contact_base',
        'label'       => $l10n->ts('Contact Data'),
        'weight'      => 10,
        'description' => '',
      ],
      'email' => [
        'name'        => 'email',
        'type'        => 'Text',
        'validation'  => 'Email',
        'weight'      => 10,
        'required'    => 1,
        'label'       => $l10n->ts('Email'),
            // $l10n->ts("Participant's email address"),
        'description' => '',
        'parent'      => 'contact_base',
      ],
    ];
  }

  /**
   * Add the default values to the form data, so people using this profile
   *  don't have to enter everything themselves
   *
   * @param \Civi\RemoteParticipant\Event\GetParticipantFormEventBase $resultsEvent
   *   the locale to use, defaults to null none. Use 'default' for current
   *
   */
  public function addDefaultValues(GetParticipantFormEventBase $resultsEvent) {
    $contact_id = $resultsEvent->getContactID();
    if ($contact_id) {
      try {
        $primary_email = civicrm_api3('Email', 'getsingle', [
          'contact_id' => $contact_id,
          'is_primary' => 1,
        ]);
        $resultsEvent->setPrefillValue('email', $primary_email['email']);
      }
      catch (CRM_Core_Exception $ex) {
        // there is no (unique) primary email
      }
    }
  }

}
