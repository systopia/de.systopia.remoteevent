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

use Civi\Api4\StateProvince;

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Test event functions with an anonymous contact
 *
 * @group headless
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_RemoteEvent_IssuesTest extends CRM_Remoteevent_TestBase {

  /**
   * Test to reproduce issue 16: translate country and state/province
   *
   * @see https://github.com/systopia/de.systopia.remoteevent/issues/16
   */
  public function testIssue16_CountryStateProvinceL10n() {
    // create an event
    $event = $this->createRemoteEvent(
        [
          'event_remote_registration.remote_registration_default_profile' => 'Standard3',
          'event_remote_registration.remote_registration_profiles' => ['Standard3'],
        ]
    );

    // get the form data
    $fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
      'event_id' => $event['id'],
    ])['values'];

    // check for Germany (English)
    self::assertTrue(isset($fields['country_id']['options']), 'Field country_id incomplete');
    $country_options = $fields['country_id']['options'];
    self::assertTrue(isset($country_options[1082]), 'Country Germany [1082] not listed');
    self::assertEquals('Germany', $country_options[1082], 'Country name not in the right language');

    // check for Germany (German: Deutschland)
    // multi-language not yet implemented
    CRM_Core_I18n::singleton()->setLocale('de_DE');
    $de_fields = $this->traitCallAPISuccess('RemoteParticipant', 'get_form', [
      'event_id' => $event['id'],
      'locale' => 'de_DE',
    ])['values'];
    self::assertTrue(isset($de_fields['country_id']['options']), 'Field country_id incomplete');
    $de_country_options = $de_fields['country_id']['options'];
    self::assertTrue(isset($de_country_options[1082]), 'Country Germany [1082] not listed');
    self::assertEquals('Deutschland', $de_country_options[1082], 'Country name not in the right language');
  }

  /**
   * Test to reproduce issue 17: state_province_id not working
   *
   * @see https://github.com/systopia/de.systopia.remoteevent/issues/17
   */
  public function testIssue17_StateProvinceId() {
    // create an event
    $event = $this->createRemoteEvent(
        [
          'event_remote_registration.remote_registration_default_profile' => 'Standard3',
          'event_remote_registration.remote_registration_profiles' => ['Standard3'],
        ]
    );

    $stateProvince = StateProvince::get(FALSE)
      ->addSelect('id', 'country_id')
      ->setLimit(1)
      ->execute()
      ->single();

    // try to register with one ClickProfile
    $contact = $this->createContact();
    $result = $this->registerRemote(
        $event['id'],
        [
          'first_name' => $contact['first_name'],
          'last_name' => $contact['last_name'],
          'email' => $contact['email'],
          'prefix_id' => 1,
          'country_id' => $stateProvince['country_id'],
          'state_province_id' => $stateProvince['country_id'] . '-' . $stateProvince['id'],
        ]
    );
    if (!empty($result['is_error'])) {
      self::fail('State/Province ID cleanup failed: ' . $result['error_message']);
    }
  }

}
