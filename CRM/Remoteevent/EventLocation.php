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
 * Functionality around the EventLocation
 */
class CRM_Remoteevent_EventLocation
{
    /** @var CRM_Remoteevent_EventLocation the single instance */
    protected static $singleton = null;

    /** @var integer event ID, or 0 if w/o event */
    protected $event_id;

    /** @var array event location contact type data */
    protected $event_location;

    /**
     * Get the event location logic for the given event ID
     *
     * @param integer $event_id
     *   event ID or 0 if general
     */
    public static function singleton($event_id)
    {
        if (self::$singleton === null || self::$singleton->event_id != $event_id) {
            self::$singleton = new CRM_Remoteevent_EventLocation($event_id);
        }
        return self::$singleton;
    }

    protected function __construct($event_id)
    {
        $this->event_id = $event_id;
        $this->event_location = null;
    }

    /**
     * Get the data of the event contact type,
     *  and creates it if it doesn't exist yet
     *
     * @return array
     *   contact type data
     */
    public function getEventContactType() {
        if ($this->event_location === null) {
            // first: we'll try to look it up
            $locations = civicrm_api3('ContactType', 'get', [
                'name' => 'Event_Location',
            ]);
            if ($locations['count'] > 1) {
                Civi::log()->warning(E::ts("Multiple matching EventLocation contact types found!"));
            }
            if ($locations['count'] > 0) {
                $this->event_location = reset($locations['values']);

            } else {
                // create it
                $new_location = civicrm_api3('ContactType', 'create', [
                    'name' => 'Event_Location',
                    'label' => E::ts("Event Location"),
                    'description' => E::ts("These contacts can be used as event locations by the RemoteEvents extension"),
                    'image_URL' => E::url('icons/event_location.png'),
                    'parent_id' => 3, // 'Organisation'
                ]);
                $this->event_location = civicrm_api3('ContactType', 'getsingle', [
                    'id' => $new_location['id']]);
            }
        }
        return $this->event_location;
    }
}
