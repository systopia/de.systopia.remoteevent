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
    /**
     * @var string
     */
    private string $classname;

    /**
     * @var int
     */
    private int $internal_id;

    /**
     * @var int
     */
    private int $select_id;

    /**
     * @var string
     */
    private string $profile_name;

    /**
     * @var string
     */
    private string $unique_id;

    /**
     * @var Additional information. For option value this is the information that
     *      is available from the API
     */
    private $params;

    /**
     * @param $classname
     * @param $profile_name
     * @param $additional_params
     */
    public function __construct(
        $classname,
        $profile_name,
        $internal_id,
        $select_id,
        $id_prefix = null,
        $additional_params = null
    ) {
        $this->classname = $classname;
        $this->profile_name = $profile_name;
        $this->internal_id = $internal_id;
        $this->params = $additional_params;
        if (empty($id_prefix)) {
            // default prefix is Option Group (og)
            $this->unique_id = 'og' . "-" . $internal_id;
        } else {
            $this->unique_id = $id_prefix . "-" . $internal_id;
        }

        $this->select_id = $select_id;
    }

    /**
     * @return string
     */
    public function get_unique_id(): string
    {
        return $this->unique_id;
    }

    /**
     * Get Classname
     *
     * @return mixed
     */
    public function getClassname()
    {
        return $this->classname;
    }

    /**
     * Get the profile Name
     *
     * @return mixed
     */
    public function getProfileName(): string
    {
        return $this->profile_name;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return int
     */
    public function get_select_counter()
    {
        return $this->select_id;
    }

    /**
     *
     * @return class instance of $this->classname if it exists
     */
    public function getInstance($profile_id = null)
    {
        if (class_exists($this->classname) && !empty($profile_id)) {
            // we have an external Profile (e.g. FormBuilder)
            return new $this->classname($this->internal_id);
        }
        // check if we have unique params, if so use them for instance, otherwise try normal instance
        if (class_exists($this->classname)) {
            return new $this->classname();
        }
    }

}