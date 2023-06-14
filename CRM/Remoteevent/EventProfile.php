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


/**
 * Data Container for EventProfile Information
 *   Used for Storing Event Information in RegistrationProfileListEvent
 */
class CRM_Remoteevent_EventProfile
{
    private string $class_name;

    private string $name;

    private string $label;

    private $new_instance_callback;

    /**
     * @param callable|null $new_instance_callback
     *   Callback to create a new profile instance. If not specified the class
     *   constructor is used.
     */
    public function __construct(
        string $class_name,
        string $name,
        string $label,
        $new_instance_callback = NULL
    ) {
        $this->class_name = $class_name;
        $this->name = $name;
        $this->label = $label;
        $this->new_instance_callback = $new_instance_callback;
    }

    public function getClassName(): string
    {
        return $this->class_name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getInstance(): CRM_Remoteevent_RegistrationProfile
    {
        if ($this->new_instance_callback !== NULL) {
            return call_user_func($this->new_instance_callback);
        }

        return new $this->class_name();
    }

}