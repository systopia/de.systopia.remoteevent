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
 * Class GetCancelParticipantFormEvent
 *
 * This event will be triggered to define the form of a new registration via
 *   RemoteParticipant.get_form API with action=cancel
 *
 * @todo: implement, this is just copied from GetCreateParticipantFormEvent
 */
class GetCancelParticipantFormEvent extends RemoteEvent
{
    /** @var array holds the original RemoteParticipant.get_form parameters */
    protected $params;

    /** @var array holds the original RemoteParticipant.get_form parameters */
    protected $result;

    public function __construct($params, $event)
    {
        $this->params = $params;
        $this->result = [];

        // todo: allow profiles? which ones?
        if (!empty($params['profile']) && $params['profile'] != 'OneClick') {
            throw new \Exception(E::ts("Only the OneClick profile is allowed for cancellation"));
        }
    }

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function &getResult()
    {
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

    /**
     * Add a current/default value to the given field
     *
     * @param string $field_name
     *   field name / key
     *
     * @param string $value
     *  default/current value to be submitted for form prefill
     */
    public function setPrefillValue($field_name, $value)
    {
        $this->result[$field_name]['value'] = $value;
    }

    /**
     * Get the parameters of the original query
     *
     * @return array
     *   parameters of the query
     */
    public function getQueryParameters()
    {
        return $this->params;
    }
}
