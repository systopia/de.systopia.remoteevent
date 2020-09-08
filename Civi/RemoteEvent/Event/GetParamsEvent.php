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
use Civi\RemoteEvent;

/**
 * Class GetParamsEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteEvent.get API call, so the search parameters can be manipulated
 */
class GetParamsEvent extends RemoteEvent
{

    /** @var array holds the original RemoteEvent.get parameters */
    protected $originalParameters;

    /** @var array holds the RemoteEvent.get parameters to be applied */
    protected $currentParameters;

    /** @var integer|false|null remote contact ID if this is a personalised query */
    protected $remote_contact_id;

    public function __construct($params)
    {
        $this->currentParameters  = $params;
        $this->originalParameters = $params;
        $this->remote_contact_id = false; // i.e. not looked up yet
    }

    /**
     * Set a parameter for the current parameters
     *
     * @param string $key
     *    parameter key
     * @param mixed $value
     *    parmeter value
     */
    public function setParameter($key, $value)
    {
        $this->currentParameters[$key] = $value;
    }

    /**
     * Returns the original parameters that were submitted to RemoteEvent.get
     *
     * @return array original parameters
     */
    public function getOriginalParameters()
    {
        return $this->originalParameters;
    }

    /**
     * Returns the current (manipulated) parameters to be submitted to Event.get
     *
     * @return array current parameters
     */
    public function getParameters()
    {
        return $this->currentParameters;
    }

    /**
     * Returns the current (manipulated) parameter
     *
     * @param string $key
     *   the parameter key
     *
     * @return mixed|null
     */
    public function getParameter($key)
    {
        return \CRM_Utils_Array::value($key, $this->currentParameters, null);
    }

    /**
     * Get the contact ID of a remote contact, this
     *   personalised query refers to
     *
     * @param array $data
     *   the data blob containing the remote_contact_id
     *
     * @return integer|null
     *   the contact ID if a valid id was passed
     */
    public function getRemoteContactID($data = [])
    {
        return parent::getRemoteContactID($this->currentParameters);
    }
}
