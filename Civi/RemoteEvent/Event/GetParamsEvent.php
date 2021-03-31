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
use Civi\RemoteParamsEvent;

/**
 * Class GetParamsEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteEvent.get API call, so the search parameters can be manipulated
 */
class GetParamsEvent extends RemoteParamsEvent
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->token_usages = ['invite', 'cancel', 'update'];
    }

    /**
     * Get the current restriction of event IDs
     *
     * Remark: this returns
     *   null  if not set
     *   fail  if couldn't be parsed
     *   array with a list of event IDs, if everything's fine
     *
     * @return array|null|string
     *   list of requested IDs
     */
    public function getRequestedEventIDs()
    {
        if (isset($this->currentParameters['id'])) {
            $id_param = $this->currentParameters['id'];
            if (is_string($id_param)) {
                // this is a single integer, or a list of integers
                $id_list = explode(',', $id_param);
                return array_map('intval', $id_list);

            } else if (is_array($id_param)) {
                // this is an array. we can deal with the 'IN' => [] notation
                if (count($id_param) == 2) {
                    if (strtolower($id_param[0]) == 'in' && is_array($id_param[1])) {
                        // this should be a list of IDs
                        return array_map('intval', $id_param[1]);
                    }
                }
            }

            // if we get here, we couldn't parse it
            \Civi::log()->debug("RemoteEvent.get: couldn't parse 'id' parameter: " . json_encode($id_param));
            return 'fail';

        } else {
            // 'id' field not set
            return null;
        }
    }

    /**
     * Restrict the query to the given event IDs.
     *  Existing restrictions will be taken into account (intersection)
     *
     * @param array $event_ids
     *   list of event IDs
     */
    public function restrictToEventIds($event_ids)
    {
        if (empty($event_ids)) {
            // this basically means: restrict to empty set:
            $this->currentParameters['id'] = 0;
        } else {
            $current_restriction = $this->getRequestedEventIDs();
            if ($current_restriction === null) {
                // no restriction set so far
                $this->currentParameters['id'] = ['IN' => $event_ids];

            } else if (is_array($current_restriction)) {
                // there is a restriction -> intersect
                $intersection = array_intersect($current_restriction, $event_ids);
                $this->currentParameters['id'] = ['IN' => $intersection];

            } else {
                // something's wrong here
                \Civi::log()->debug("RemoteEvent.get: couldn't restrict 'id' parameter: " . json_encode($current_restriction));
            }
        }
    }
}
