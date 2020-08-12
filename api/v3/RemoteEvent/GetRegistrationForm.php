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

require_once 'remoteevent.civix.php';
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * RemoteEvent.get_registration_form specification
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_event_get_registration_form_spec(&$spec)
{
    $spec['event_id'] = [
        'name'         => 'event_id',
        'api.required' => 1,
        'title'        => E::ts('Event ID'),
        'description'  => E::ts('Internal ID of the event the registration form is needed for'),
    ];
    $spec['profile'] = [
        'name'         => 'profile',
        'api.required' => 0,
        'title'        => E::ts('Profile Name'),
        'description'  => E::ts('If omitted, the default profile is used'),
    ];
    $spec['remote_contact_id'] = [
        'name'         => 'remote_contact_id',
        'api.required' => 0,
        'title'        => E::ts('Remote Contact ID'),
        'description'  => E::ts('You can submit a remote contact, in which case the fields should come with the default data'),
    ];
    $spec['locale'] = [
        'name'         => 'locale',
        'api.required' => 0,
        'title'        => E::ts('Locale'),
        'description'  => E::ts('Locale of the field labels/etc. NOT IMPLEMENTED YET'),
    ];
}

/**
 * RemoteEvent.get_registration_form implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_remote_event_get_registration_form($params)
{
    // first: sanity checks
    // 1) does the event exist?
    $event_query = civicrm_api3('RemoteEvent', 'get', ['id' => $params['event_id']]);
    if ($event_query['count'] < 1) {
        return civicrm_api3_create_error(
            E::ts("RemoteEvent [%1] does not exist, or not eligible for registration.", [1 => $params['event_id']])
        );
    } elseif ($event_query['count'] > 1) {
        return civicrm_api3_create_error(
            E::ts("RemoteEvent [%1] is ambiguous.", [1 => $params['event_id']])
        );
    }
    $event = reset($event_query['values']);

    // 2) is remote registration enabled for this event?
    if (empty($event['remote_registration_enabled'])) {
        return civicrm_api3_create_error(
            E::ts("RemoteEvent [%1] has no remote registration enabled.", [1 => $params['event_id']])
        );
    }

    // 3) is the event still open for registration?
    // todo: how to check that?

    // evaluate the profile
    if (empty($params['profile'])) {
        // use default profile
        $params['profile'] = $event['default_profile'];
    }
    $allowed_profiles = explode(',', $event['enabled_profiles']);
    if (!in_array($params['profile'], $allowed_profiles)) {
        return civicrm_api3_create_error(
            E::ts("Profile [%2] cannot be used with RemoteEvent [%1].", [
                1 => $params['event_id'],
                2 => $params['profile']
            ])
        );
    }

    // get locale
    $locale = CRM_Utils_Array::value('locale', $params, CRM_Core_I18n::getLocale());

    // compile field list
    $field_list = [];
    // add profile fields
    $profile = CRM_Remoteevent_RegistrationProfile::getRegistrationProfile($params['profile']);
    $field_list = array_merge($field_list, $profile->getFields($locale));

    // add more fields: @todo: use symfony events?
    // TODO: add event fields (e.g. role)

    // add default values
    // TODO: add default values if remote_contact_id is given

    return civicrm_api3_create_success($field_list);
}
