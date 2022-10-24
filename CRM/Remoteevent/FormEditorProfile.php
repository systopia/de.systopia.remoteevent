<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2022 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use Civi\RemoteEventFormEditor\FieldType\FieldTypeGroupContainer;
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Data Container for RemoteEventFormBuilder Data
 */
class CRM_Remoteevent_FormEditorProfile extends CRM_Remoteevent_RegistrationProfile
{

    /**
     * @var string 
     */
    private static $classname = 'CRM_Remoteevent_RegistrationProfile_FormEditor';

    /**
     * profile id in remoteventformeditor
     *
     * @var int
     */
    private int $id;

    /**
     * profile name in remoteventformeditor
     *
     * @var string
     */
    private string $name;

    /**
     * form values in object notation
     *
     * @var mixed
     */
    private $form_values;

    /**
     * @var string[]
     */
    private $field_mapping = [
        'name' => 'target',
        'type' => 'validation',
        'validation' => 'validation',
        'required' => 'required',
        'label' => 'label',
        'description' => 'description',
        'maxlength' => 'maxlength',
    ];

    /**
     * @param $name
     *
     * @return string
     */
    public function getName($name = null)
    {
        return 'fb-' . $this->id;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->name;
    }

    public function addDefaultValues(
        \Civi\RemoteParticipant\Event\GetParticipantFormEventBase $resultsEvent,
        $name = null
    ) {
        // TODO: Implement addDefaultValues() method.
    }

    /**
     * @param $id
     * @param $name
     * @param $values
     */
    public function __construct($id, $name, $values)
    {
        $this->id = $id;
        $this->name = $name;
        $this->form_values = json_decode($values);
    }

    /**
     * @return mixed
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_class_name(): string
    {
        return self::$classname;
    }

    /**
     * @param $locale
     *
     * @return array
     */
    public function getFields($locale = null)
    {
        $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
        $form = [];
        $current_fieldset = '';
        $current_weight = 0;
        foreach ($this->form_values as $field) {
            if ($field->type == "fieldset") {
                // TODO parse fieldset
                // move to function
                // replace spaces with underscores for name
                $fieldset_name = str_replace(" ", "_", $field->label);
                $form[$fieldset_name] = [];
                $this->set_fieldSet($form[$fieldset_name], $field, $l10n, $current_weight, $fieldset_name);
                ++$current_weight;
                // iterate over items array for fieldset content
                foreach ($field->items as $fieldset_items) {
                    $group_index = $fieldset_items->type;
                    $form[$group_index] = [];
                    $this->set_fields($form[$group_index], $fieldset_items, $fieldset_name);
                    ++$current_weight;
                }
            } else {
                // parse normal data
                $fieldset_name = $field->type;
                $form[$fieldset_name] = [];
                $tmp = \Civi::service(\Civi\RemoteEventFormEditor\FieldType\FieldTypeContainer::class);
                $test = $tmp->getFieldType($field->type);
                $this->set_fields($form[$fieldset_name], $field);
                // Setting weight currently just counting up, since the order is already set in the JSON data
                // but no real weight attribute  is present
                // TODO: Check what is required here
                $form[$fieldset_name]['weight'] = $current_weight;
                ++$current_weight;
            }
        }
        return $form;
    }

    /**
     * @param $form
     * @param $values
     * @param $l10n
     * @param $current_weight
     * @param $fieldset_name
     *
     * @return void
     */
    private function set_fieldSet(&$form, $values, $l10n, $current_weight, $fieldset_name)
    {
        //        $fieldset_name = str_replace(" ", "_", $values['label']);
        $form['type'] = 'fieldset';
        $form['name '] = $fieldset_name;
        $form['label'] = $l10n->localise($values->label);
        $form['weight'] = $current_weight;
        $form['description'] = "";
    }

    /**
     * @param $form
     * @param $values
     * @param $parent
     *
     * @return void
     */
    private function set_fields(&$form, $values, $parent = null)
    {
        $type = $values->type;
        foreach ($this->field_mapping as $field => $form_value) {
            if (isset($values->{$form_value})) {
                $form[$field] = $values->{$form_value};
            }
        }
        // set allowed options
        if (isset($values->allowedOptions)) {
            $field_container = \Civi::service(\Civi\RemoteEventFormEditor\FieldType\FieldTypeContainer::class);
            $container_type = $field_container->getFieldType($type);
            $extra_data = $container_type->getExtraData();
            $form['options'] = array_flip($extra_data['options']);
            // Overwrite html_type
            if (isset($extra_data['multiple'])) {
                $form['type'] = "Multi-Select";
            } else {
                $form['type'] = "Select";
            }
        }
        // set parent for group entries
        if (!empty($parent)) {
            $form['parent'] = $parent;
        }
    }

}