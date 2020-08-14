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


namespace Civi\RemoteEvent\Event;

/**
 * Class GetParamsEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteEvent.get API call, so the search parameters can be manipulated
 */
class GetRegistrationFormResultsEvent extends \Symfony\Component\EventDispatcher\Event {

    /** @var array holds the original RemoteEvent.get_registration_form parameters */
    protected $params;

    /** @var array holds the event data of the event involved */
    protected $event;

    /** @var array holds the RemoteEvent.get_registration_form result to be modified/extended */
    protected $result;


    public function __construct($params, $event, $result = [])
    {
        $this->params = $params;
        $this->event  = $event;
        $this->result = $result;
    }

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function getEvent() {
        return $this->event;
    }

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Add a number of field specs to the result
     *
     * @param array $field_list
     *   fields to add
     */
    public function addFields($field_list)
    {
        foreach ($field_list as $key => $field_spec) {
            $this->result[$key] = $field_spec;
        }
    }

}
