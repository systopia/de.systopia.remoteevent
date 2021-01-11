<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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

class CRM_Remoteevent_Page_SessionParticipantList extends CRM_Core_Page
{

    public function run()
    {
        // load data
        $session_id = CRM_Utils_Request::retrieve('session_id', 'String', $this, true);
        $session = civicrm_api3('Session', 'getsingle', [
            'id'     => $session_id,
            'return' => 'title,event_id,id'
        ]);
        CRM_Utils_System::setTitle(E::ts('Participant List for Session "%1"', [1 => $session['title']]));

        // load the participants
        $raw_participants = CRM_Remoteevent_BAO_Session::getSessionRegistrations($session_id);
        $participant_ids = [];
        foreach ($raw_participants as $participant) {
            $participant_ids[] = $participant['id'];
        }
        if (empty($participant_ids)) {
            $participant_ids = [0];
        }
        $participant_query = civicrm_api3('Participant', 'get', [
            'id'           => ['IN' => $participant_ids],
            'option.limit' => 0,
            'option.sort'  => 'last_name asc',
            'return'       => 'id,contact_id,display_name,participant_role,participant_status'
        ]);

        // compile list
        $participants = $participant_query['values'];
        foreach ($participants as &$participant) {
            if (is_array($participant['participant_role'])) {
                $participant['participant_role'] = implode(', ', $participant['participant_role']);
            }

            // add participant link
            $link = CRM_Utils_System::url('civicrm/contact/view/participant',
            "action=view&reset=1&context=sessions&id={$participant['id']}&cid={$participant['contact_id']}");
            $link_title = E::ts("View");
            $participant['link'] = "<a class='crm-popup' href='{$link}'>{$link_title}</a>";

            // add contact link
            $participant['contact_link'] = CRM_Utils_System::url('civicrm/contact/view',
                                                                 "reset=1&cid={$participant['contact_id']}");

            // add is_counted
            $participant['is_counted'] = $raw_participants[$participant['id']]['participant_counts'];
        }

        $this->assign('participants', $participants);

        parent::run();
    }

}
