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

/**
 * Class GetCreateParticipantFormEvent
 *
 * This event will be triggered to define the form of a new registration via
 *   RemoteParticipant.get_form API with context=create
 */
class GetCreateParticipantFormEvent extends GetParticipantFormEventBase
{
    /**
     * GetCreateParticipantFormEvent constructor.
     */
    public function __construct($params, $event)
    {
        parent::__construct($params, $event);

        // add 'confirm' field if there already is a participant
        if ($this->getParticipantID()) {
            $l10n = $this->getLocalisation();
            $this->addFields([
                'confirm' => [
                     'name'        => 'confirm',
                     'type'        => 'Select',
                     'options'     => [1 => $l10n->localise('Confirm'), 0 => $l10n->localise('Decline')],
                     'validation'  => '',
                     'weight'      => 10,
                     'required'    => 0,
                     'label'       => $l10n->localise('Confirm Invitation'),
                     'description' => $l10n->localise('Do you accept the invitation?'),
                 ],
            ]);
        }
    }


    /**
     * Get the token usage key for this event type
     *
     * @return string
     */
    protected function getTokenUsage()
    {
        return 'invite';
    }
}
