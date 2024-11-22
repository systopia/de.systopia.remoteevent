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


namespace Civi\RemoteParticipant\Event;
use Civi\RemoteEvent;
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Class GetCreateParticipantFormEvent
 *
 * This event will be triggered to define the form of a new registration via
 *   RemoteParticipant.get_form API with context=create
 */
class GetCreateParticipantFormEvent extends GetParticipantFormEventBase
{

    public const NAME = 'civi.remoteevent.registration.getform';

    /**
     * GetCreateParticipantFormEvent constructor.
     */
    public function __construct($params, $event)
    {
        parent::__construct($params, $event);

        // add 'confirm' field if there already is a participant
        $participant_id = $this->getParticipantID();
        if ($participant_id) {
            try {
                // check if the participant in status 'Invited'
                $participant_status_id = (int) \civicrm_api3('Participant', 'getvalue', [
                    'id'     => $participant_id,
                    'return' => 'participant_status_id'
                ]);
                $status_name = \CRM_Remoteevent_Registration::getParticipantStatusName($participant_status_id);
                if ($status_name == 'Invited') {
                    // this IS an invitation
                    $l10n = $this->getLocalisation();
                    $this->addFields([
                         'confirm' => [
                             'name'        => 'confirm',
                             'type'        => 'Select',
                             'options'     => [
                                 1 => $l10n->ts('Accept Invitation'),
                                 0 => $l10n->ts('Decline Invitation'),
                             ],
                             'value'       => \Civi::settings()->get('remote_registration_invitation_confirm_default_value') ?? 0,
                             'validation'  => '',
                             'weight'      => -10,
                             'required'    => 1,
                             'label'       => $l10n->ts('Invitation Feedback'),
                         ],
                     ]);

                } else {
                    // todo: there IS a participant, and it's NOT an invite. anything to do here?

                }
            } catch (\CiviCRM_API3_Exception $ex) {
                // the participant probably doesn't exist:
                $this->addWarning(E::ts("The link or reference you're using is no longer valid."));
            }
        }
    }


    /**
     * Get the token usage key for this event type
     *
     * @return array
     */
    protected function getTokenUsages()
    {
        return ['invite'];
    }
}
