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
    private $classname;

    private $profile_name;

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
    public function __construct($classname, $profile_name, $additional_params)
    {
        $this->classname = $classname;
        $this->profile_name = $profile_name;
        $this->params = $additional_params;
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
    public function getProfileName()
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
     *
     * @return class instance of $this->classname if it exists
     */
    public function getProfileInstance()
    {
        if (class_exists($this->classname)) {
            return new $this->classname();
        }
    }

}