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
                'link'    => CRM_Utils_System::url('civicrm/event/manage/registrationconfig', "action=update&reset=1&id={$event_id}"),
                'valid'   => 1,
                'active'  => 1,
                'current' => false,
            ];
        } else {
            $tabs['registrationconfig'] = [
                'title'   => E::ts("Remote Online Registration"),
                'url'     => 'civicrm/event/manage/registrationconfig',
                //'field'   => '??' set to some trigger field to highlight
            ];
        }

        // lastly: rename the registration tab and move to the end
        if (isset($tabs['registration'])) {
            $classic_registration = $tabs['registration'];
            $classic_registration['title'] = E::ts("Online Registration (Internal)");
            unset($tabs['registration']);

            // todo: setting - do we still want this?
            //$tabs['registration'] = $classic_registration;
        }
    }
}
