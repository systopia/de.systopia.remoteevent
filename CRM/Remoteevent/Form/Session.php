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
 * Session Editor
 */
class CRM_Remoteevent_Form_Session extends CRM_Core_Form
{

    protected function getSessionID() {
        return null; // todo
    }

    public function buildQuickForm()
    {
        $this->add(
            'text',
            'title',
            E::ts("Session Title"),
            ['class' => 'huge'],
            true
        );

        $this->add(
            'datepicker',
            'start_date',
            E::ts("Session Start"),
            [],
            true,
            ['time' => true]
        );

        $this->add(
            'datepicker',
            'end_date',
            E::ts("Session End"),
            [],
            true,
            ['time' => true]
        );

        $this->add(
            'select',
            'slot_id',
            E::ts("Session Slot"),
            $this->getOptionValues('session_slot', "No Slot"),
            false,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'type_id',
            E::ts("Session Type"),
            $this->getOptionValues('session_type'),
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'category_id',
            E::ts("Session Category"),
            $this->getOptionValues('session_category'),
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'wysiwyg',
            'description',
            E::ts('Session Description'),
            ['class' => 'collapsed'],
            false
        );

        $this->add(
            'wysiwyg',
            'location',
            E::ts('Session Location'),
            ['class' => 'collapsed'],
            false
        );

        $this->add(
            'text',
            'max_participants',
            E::ts('Maximum Participants'),
            ['class' => 'tiny'],
            false
        );

        $this->add(
            'text',
            'presenter_title',
            E::ts('Presenter Title'),
            ['class' => 'short'],
            false
        );

        $this->addEntityRef(
            'presenter_id',
            E::ts('Presenter'),
            [
                'entity' => 'Contact',
                'api' => ['params' => ['is_deleted' => 0, 'limit' => 20]],
            ],
            false
        );

        $this->addButtons(
            [
                [
                    'type' => 'submit',
                    'name' => $this->getSessionID() ? E::ts('Update') :  E::ts('Create'),
                    'isDefault' => true,
                ],
            ]
        );

        parent::buildQuickForm();
    }


    public function postProcess()
    {
        $values = $this->exportValues();
        CRM_Core_Session::setStatus(
            E::ts(
                'You picked color "%1"',
                [
                    1 => $options[$values['favorite_color']],
                ]
            )
        );
        parent::postProcess();
    }

    /**
     * Get a list of all available categories
     * @return array
     *   list of slot_id -> slot label
     */
    protected function getOptionValues($option_group_name, $none_option = null)
    {
        $values = [];
        if ($none_option) {
            $values[''] = $none_option;
        }
        $value_query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => $option_group_name,
            'option.limit'    => 0,
            'return'          => 'value,label'
        ]);
        foreach ($value_query['values'] as $value) {
            $values[$value['value']] = $value['label'];
        }
        return $values;
    }
}
