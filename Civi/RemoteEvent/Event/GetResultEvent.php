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
 * Class GetResultEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event will be triggered to manipulate and extend the
 *   output of RemoteEvent.get
 */
class GetResultEvent extends RemoteEvent
{

    /** @var array holds the original RemoteEvent.get parameters */
    protected $original_params;

    /** @var array holds the executed RemoteEvent.get parameters */
    protected $params;

    /** @var array holds the RemoteEvent.get parameters to be applied */
    protected $event_data;

    /**
     * GetResultEvent constructor.
     * @param array $params
     *   the executed parameters of the Event.get query
     *
     * @param array $values
     *   the list of events to return
     *
     * @param array $original_params
     *   the original parameters of the Event.get query
     */
    public function __construct($params, $event_data, $original_params)
    {
        $this->params = $params;
        $this->original_params = $original_params;
        $this->event_data = $event_data;
        $this->token_usages = ['invite', 'cancel', 'update'];
    }

    /**
     * Returns the current event data to be returned to the caller
     *
     * @return array event data (list)
     */
    public function &getEventData()
    {
        return $this->event_data;
    }

    /**
     * Trim the result to the given limit
     *
     * @param integer $limit
     *   the limit to trim to. if 0, no trimming will be done
     */
    public function trimToLimit($limit)
    {
        if ($limit) {
            $this->event_data = array_splice($this->event_data, 0, $limit);
        }
    }

    /**
     * Returns the current (manipulated) parameters to be submitted to Event.get
     *
     * @return array parameters used in the selection
     */
    public function getSelectionParameters()
    {
        return $this->params;
    }

    /**
     * Returns the original parameters to be submitted to Event.get by the client
     *
     * @return array parameters originally submitted
     */
    public function getOriginalParameters()
    {
        return $this->original_params;
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

    /**
     * Get the limit parameter of the original reuqest
     *
     * @return integer
     *   returned result count or 0 for 'no limit'
     */
    public function getOriginalLimit()
    {
        // check the options array
        if (isset($this->original_params['options']['limit'])) {
            return (int) $this->original_params['options']['limit'];
        }

        // check the old-fashioned parameter style
        if (isset($this->original_params['option.limit'])) {
            return (int) $this->original_params['option.limit'];
        }

        // default is '25' (by general API contract)
        return 25;
    }

}
