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

use CRM_Remoteevent_ExtensionUtil as E;
use Civi\RemoteParticipant\Event\GetParticipantFormEventBase as GetParticipantFormEventBase;


/**
 * Implements profile 'Standard3': Email, prefix, title, first name, last name, postal address and phone",
 */
class CRM_Remoteevent_RegistrationProfile_Standard3 extends CRM_Remoteevent_RegistrationProfile_Standard2
{
    /**
     * Get the internal name of the profile represented
     *
     * @return string name
     */
    public function getName()
    {
        return 'Standard3';
    }

    /**
     * @param string $locale
     *   the locale to use, defaults to null (current locale)
     *
     * @return array field specs
     * @see CRM_Remoteevent_RegistrationProfile::getFields()
     *
     */
    public function getFields($locale = null)
    {
        $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
        return array_merge(
            parent::getFields($locale),
            [
                'contact_base' => [
                    'type'        => 'fieldset',
                    'name'        => 'contact_base',
                    'label'       => $l10n->ts("Contact Data"),
                    'weight'      => 10,
                    'description' => '',
                ],
                'phone'                  => [
                    'name'        => 'phone',
                    'type'        => 'Text',
                    'validation'  => '',
                    'maxlength'   => 32,
                    'weight'      => 100,
                    'required'    => 0,
                    'label'       => $l10n->ts('Phone Number'),
                    'description' => $l10n->ts("Please include country code"),
                    'parent'      => 'contact_base',
                ],

                'contact_address' => [
                    'type'        => 'fieldset',
                    'name'        => 'contact_base',
                    'label'       => $l10n->ts("Contact Address"),
                    'weight'      => 20,
                    'description' => '',
                ],
                'street_address'         => [
                    'name'        => 'street_address',
                    'type'        => 'Text',
                    'validation'  => '',
                    'maxlength'   => 96,
                    'weight'      => 10,
                    'required'    => 0,
                    'label'       => $l10n->ts('Street Address'),
                    'description' => $l10n->ts("Participant's street and house number"),
                    'parent'      => 'contact_address',
                ],
                'supplemental_address_1' => [
                    'name'        => 'supplemental_address_1',
                    'type'        => 'Text',
                    'validation'  => '',
                    'maxlength'   => 96,
                    'weight'      => 20,
                    'required'    => 0,
                    'label'       => $l10n->ts('Supplemental Address'),
                    'parent'      => 'contact_address',
                ],
                'supplemental_address_2' => [
                    'name'        => 'supplemental_address_2',
                    'type'        => 'Text',
                    'validation'  => '',
                    'maxlength'   => 96,
                    'weight'      => 30,
                    'required'    => 0,
                    'label'       => $l10n->ts('Supplemental Address 2'),
                    'parent'      => 'contact_address',
                ],
                'postal_code'            => [
                    'name'        => 'postal_code',
                    'type'        => 'Text',
                    'validation'  => '',
                    'maxlength'   => 64,
                    'weight'      => 40,
                    'required'    => 0,
                    'label'       => $l10n->ts('Postal Code'),
                    'parent'      => 'contact_address',
                ],
                'city'                   => [
                    'name'        => 'city',
                    'type'        => 'Text',
                    'validation'  => '',
                    'maxlength'   => 64,
                    'weight'      => 50,
                    'required'    => 0,
                    'label'       => $l10n->ts('City'),
                    'parent'      => 'contact_address',
                ],
                'country_id'             => [
                    'name'        => 'country_id',
                    'type'        => 'Select',
                    'options'     => $this->getCountries($locale),
                    'validation'  => '',
                    'weight'      => 60,
                    'required'    => 0,
                    'label'       => $l10n->ts('Country'),
                    'parent'      => 'contact_address',
                    'dependencies'=> [
                        [
                            'dependent_field'       => 'state_province_id',
                            'hide_unrestricted'     => 1,
                            'hide_restricted_empty' => 1,
                            'command'               => 'restrict',
                            'regex_subject'         => 'dependent',
                            'regex'                 => '^({current_value}-[0-9]+)$',
                        ],
                    ],
                ],
                'state_province_id'    => [
                    'name'        => 'state_province_id',
                    'type'        => 'Select',
                    'validation'  => '',
                    'weight'      => 70,
                    'required'    => 0,
                    'options'     => $this->getStateProvinces($locale),
                    'label'       => $l10n->ts('State or Province'),
                    'parent'      => 'contact_address'
                ],
            ]
        );
    }

    /**
     * Give the profile a chance to manipulate the contact data before it's being sent off to
     *   the contact creation/update
     *
     * @param array $contact_data
     *   contact data
     */
    protected function adjustContactData(&$contact_data)
    {
        // fix the combined state_province_id value
        if (!empty($contact_data['state_province_id'])) {
            $country_and_state = explode('-', $contact_data['state_province_id']);
            $contact_data['state_province_id'] = end($country_and_state);
        }

        // don't forget other adjustments
        parent::adjustContactData($contact_data);
    }

    /**
     * Add the default values to the form data, so people using this profile
     *  don't have to enter everything themselves
     *
     * @param GetParticipantFormEventBase $resultsEvent
     *   the locale to use, defaults to null none. Use 'default' for current
     *
     */
    public function addDefaultValues(GetParticipantFormEventBase $resultsEvent)
    {
        // add contact data
        $this->addDefaultContactValues($resultsEvent, ['prefix_id', 'email', 'formal_title', 'first_name', 'last_name', 'phone']);

        // add address data (primary?)
        $contact_id = $resultsEvent->getContactID();
        if ($contact_id) {
            $address_fields = ['street_address', 'supplemental_address_1', 'supplemental_address_2', 'postal_code', 'city', 'country_id', 'state_province_id'];
            try {
                $address = civicrm_api3(
                    'Address',
                    'getsingle',
                    [
                        'contact_id' => $contact_id,
                        'is_primary' => 1,
                        'return' => implode(',', $address_fields)
                    ]
                );
                foreach ($address_fields as $address_field) {
                    $resultsEvent->setPrefillValue($address_field, $address[$address_field]);
                }
            } catch (CiviCRM_API3_Exception $ex) {
                // probably no primary address set
            }
        }
    }
}
