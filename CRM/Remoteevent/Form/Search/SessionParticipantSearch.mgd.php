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

return [
    0 =>
        [
            'name' => 'CRM_Remoteevent_Form_Search_SessionParticipantSearch',
            'entity' => 'CustomSearch',
            'params' =>
                [
                    'version' => 3,
                    'label'       => E::ts('Session Participants Search'),
                    'description' => E::ts('Find contacts that registered for selected sessions'),
                    'class_name' => 'CRM_Remoteevent_Form_Search_SessionParticipantSearch',
                ],
        ],
];
