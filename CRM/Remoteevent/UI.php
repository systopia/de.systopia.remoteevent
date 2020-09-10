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


/**
 * UI changes to CiviCRM's generic event UI
 */
class CRM_Remoteevent_UI
{
    /**
     * Manipulates the Implements hook_civicrm_tabset
     *
     * @param integer $event_id
     *      the event in question
     *
     * @param array $tabs
     *      tab structure to be displayed
     */
    public static function updateEventTabs($event_id, &$tabs)
    {
        // then add new registration tab
        if ($event_id) {
            $tabs['registrationconfig'] = [
                'title'   => E::ts("Remote Online Registration"),
                'link'    => CRM_Utils_System::url(
                    'civicrm/event/manage/registrationconfig',
                    "action=update&reset=1&id={$event_id}"
                ),
                'valid'   => 1,
                'active'  => 1,
                'current' => false,
            ];
            if (CRM_Remoteevent_EventFlags::isRemoteRegistrationEnabled($event_id)
                && CRM_Remoteevent_EventFlags::isAlternativeLocationEnabled($event_id)) {
                // remove old location
                unset($tabs['location']);

                // add new location
                $tabs['alternativelocation'] = [
                    'title'   => E::ts("Event Location"),
                    'link'    => CRM_Utils_System::url(
                        'civicrm/event/manage/alternativelocation',
                        "action=update&reset=1&id={$event_id}"
                    ),
                    'valid'   => 1,
                    'active'  => 1,
                    'current' => false,
                ];
            }

        } else {
            // these are the fields that apply to *all* events (in the management screen)
            $tabs['registrationconfig'] = [
                'title' => E::ts("Remote Online Registration"),
                'url'   => 'civicrm/event/manage/registrationconfig',
                //'field'   => '??' set to some trigger field to highlight
            ];
        }

        // lastly: remove the native registration tab
        if (isset($tabs['registration'])) {
            $event_id = (int) $event_id;
            if ($event_id) {
                $native_registration_disabled = CRM_Remoteevent_EventFlags::isNativeOnlineRegistrationDisabled($event_id);
                if ($native_registration_disabled) {
                    unset($tabs['registration']);
                } else {
                    $tabs['registration']['title'] = E::ts("Online Registration (CiviCRM)");
                }
            }
        }
    }
}
