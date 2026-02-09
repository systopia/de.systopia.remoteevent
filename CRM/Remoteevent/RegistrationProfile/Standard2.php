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
 * Implements profile 'Standard2': Email, prefix, title, first and last name
 */
class CRM_Remoteevent_RegistrationProfile_Standard2 extends CRM_Remoteevent_RegistrationProfile {

  /**
   * Get the internal name of the profile represented
   *
   * @return string name
   */
  public function getName() {
    return 'Standard2';
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
      'email'        => [
        'name'        => 'email',
        'type'        => 'Text',
        'validation'  => 'Email',
        'weight'      => 10,
        'required'    => 1,
        'label'       => $l10n->ts('Email'),
          //$l10n->ts("Participant's email address"),
        'description' => '',
        'parent'      => 'contact_base',
      ],
      'prefix_id'    => [
        'name'        => 'prefix_id',
        'type'        => 'Select',
        'validation'  => '',
        'weight'      => 20,
        'required'    => 1,
        'options'     => $this->getOptions('individual_prefix', $locale),
        'label'       => $l10n->ts('Prefix'),
          //$l10n->ts("Participant's Prefix"),
        'description' => '',
        'parent'      => 'contact_base',
      ],
      'formal_title' => [
        'name'        => 'formal_title',
        'type'        => 'Text',
        'validation'  => '',
        'maxlength'   => 64,
        'weight'      => 30,
        'required'    => 0,
        'label'       => $l10n->ts('Title'),
          //$l10n->ts("Participant's Formal Title"),
        'description' => '',
        'parent'      => 'contact_base',
      ],
      'first_name'   => [
        'name'        => 'first_name',
        'type'        => 'Text',
        'validation'  => '',
        'maxlength'   => 64,
        'weight'      => 40,
        'required'    => 1,
        'label'       => $l10n->ts('First Name'),
          //$l10n->ts("Participant's First Name"),
        'description' => '',
        'parent'      => 'contact_base',
      ],
      'last_name'    => [
        'name'        => 'last_name',
        'type'        => 'Text',
        'validation'  => '',
        'maxlength'   => 64,
        'weight'      => 50,
        'required'    => 1,
        'label'       => $l10n->ts('Last Name'),
          //$l10n->ts("Participant's Last Name"),
        'description' => '',
        'parent'      => 'contact_base',
      ],
    ];
  }

}
