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
use \Civi\RemoteEvent\Event\GetResultEvent as GetResultEvent;
use \Civi\RemoteEvent\Event\GetFieldsEvent as GetFieldsEvent;

/**
 * Functionality around the EventLocation
 */
class CRM_Remoteevent_EventLocation
{
    const FIELDS = [
        'location_name',
        'location_remark',
        'location_street_address',
        'location_postal_code',
        'location_city',
        'location_country_id',
        'location_supplemental_address_1',
        'location_supplemental_address_2',
        'location_supplemental_address_3',
        'location_geo_code_1',
        'location_geo_code_2',
    ];

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

    /**
     * Extend the data returned by RemoteEvent.get by the location data
     *
     * @param GetResultEvent $result
     */
    public static function addLocationData(GetResultEvent $result)
    {
        // extract event_ids
        $events = [];
        foreach ($result->getEventData() as &$event) {
            unset($event['event_alternative_location.event_alternativelocation_remark']);
            unset($event['event_alternative_location.event_alternativelocation_contact_id']);
            $events[$event['id']] = &$event;
        }
        if (empty($events)) {
            // nothing to do
            return;
        }

        // process all that have an alternative event location
        $event_id_list = implode(',', array_keys($events));
        $alternative_location_query = "
            SELECT
              event.id                                AS event_id,
              location_contact.organization_name      AS location_name,
              alt_location.remark                     AS location_remark,
              location_address.street_address         AS location_street_address,
              location_address.postal_code            AS location_postal_code,
              location_address.city                   AS location_city,
              location_address.country_id             AS location_country_id,
              location_address.supplemental_address_1 AS location_supplemental_address_1,
              location_address.supplemental_address_2 AS location_supplemental_address_2,
              location_address.supplemental_address_3 AS location_supplemental_address_3,
              location_address.geo_code_1             AS location_geo_code_1,
              location_address.geo_code_2             AS location_geo_code_2
            FROM civicrm_event event
            LEFT JOIN civicrm_value_remote_registration settings
                   ON settings.entity_id = event.id
            LEFT JOIN civicrm_value_event_alternative_location alt_location
                   ON alt_location.entity_id = event.id
            LEFT JOIN civicrm_contact location_contact
                   ON alt_location.contact_id = location_contact.id
            LEFT JOIN civicrm_address location_address
                   ON location_address.contact_id = location_contact.id
                  AND location_address.is_primary = 1
            WHERE event.id IN ({$event_id_list})
              AND settings.use_custom_event_location = 1
            GROUP BY event.id";
        $query = CRM_Core_DAO::executeQuery($alternative_location_query);
        while ($query->fetch()) {
            $event = &$events[$query->event_id];
            foreach (self::FIELDS as $field_name) {
                $event[$field_name] = $query->$field_name;
            }
            unset($events[$query->event_id]);
        }

        // for the remaining events, get the default event data
        $remaining_event_list = implode(',', array_keys($events));
        if (!empty($remaining_event_list)) {
            $native_location_query = "
            SELECT
              event.id                                AS event_id,
              event.title                             AS location_name,
              ''                                      AS location_remark,
              location_address.street_address         AS location_street_address,
              location_address.postal_code            AS location_postal_code,
              location_address.city                   AS location_city,
              location_address.country_id             AS location_country_id,
              location_address.supplemental_address_1 AS location_supplemental_address_1,
              location_address.supplemental_address_2 AS location_supplemental_address_2,
              location_address.supplemental_address_3 AS location_supplemental_address_3,
              location_address.geo_code_1             AS location_geo_code_1,
              location_address.geo_code_2             AS location_geo_code_2
            FROM civicrm_event event
            LEFT JOIN civicrm_loc_block location_block
                   ON location_block.id = event.loc_block_id
            LEFT JOIN civicrm_address location_address
                   ON location_address.id = location_block.address_id
            WHERE event.id IN ({$remaining_event_list})
            GROUP BY event.id";
            $query = CRM_Core_DAO::executeQuery($native_location_query);
            while ($query->fetch()) {
                $event = &$events[$query->event_id];
                foreach (self::FIELDS as $field_name) {
                    $event[$field_name] = $query->$field_name;
                }
            }
        }
    }

    /**
     * Add the fields to the RemoteEvent.get_fields list
     *
     * @param GetFieldsEvent $fields_collection
     */
    public static function addFieldSpecs($fields_collection)
    {
        // remove the custom fields from the list, we're adding the unified fields below
        $fields_collection->removeFieldSpec('event_alternative_location.event_alternativelocation_remark');
        $fields_collection->removeFieldSpec('event_alternative_location.event_alternativelocation_contact_id');

        // add unified location fields (used by native and alternative location)
        $fields_collection->setFieldSpec('location_name', [
            'name'          => 'location_name',
            'type'          => CRM_Utils_Type::T_STRING,
            'title'         => "Location Name",
            'description'   => "Name of the location",
            'localizable'   => 0,
            'is_core_field' => false,

        ]);
        $fields_collection->setFieldSpec('location_remark', [
            'name'          => 'location_remark',
            'type'          => CRM_Utils_Type::T_LONGTEXT,
            'title'         => "Location Remark",
            'description'   => "Additional information for this location, unique to the event",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
        $fields_collection->setFieldSpec('location_street_address', [
            'name'          => 'location_street_address',
            'type'          => CRM_Utils_Type::T_STRING,
            'title'         => "Location Street Address",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
        $fields_collection->setFieldSpec('location_postal_code', [
            'name'          => 'location_postal_code',
            'type'          => CRM_Utils_Type::T_STRING,
            'title'         => "Location Postal Code",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
        $fields_collection->setFieldSpec('location_city', [
            'name'          => 'location_city',
            'type'          => CRM_Utils_Type::T_STRING,
            'title'         => "Location City",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
        $fields_collection->setFieldSpec('location_country_id', [
            'name'          => 'location_country_id',
            'type'          => CRM_Utils_Type::T_INT,
            'title'         => "Location Country ID",
            'localizable'   => 0,
            'is_core_field' => false,

        ]);
        $fields_collection->setFieldSpec('location_supplemental_address_1', [
            'name'          => 'location_supplemental_address_1',
            'type'          => CRM_Utils_Type::T_STRING,
            'title'         => "Location Supplemental Address 1",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
        $fields_collection->setFieldSpec('location_supplemental_address_2', [
            'name'          => 'location_supplemental_address_2',
            'type'          => CRM_Utils_Type::T_STRING,
            'title'         => "Location Supplemental Address 2",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
        $fields_collection->setFieldSpec('location_supplemental_address_3', [
            'name'          => 'location_supplemental_address_3',
            'type'          => CRM_Utils_Type::T_STRING,
            'title'         => "Location Supplemental Address 3",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
        $fields_collection->setFieldSpec('location_geo_code_1', [
            'name'          => 'location_geo_code_1',
            'type'          => CRM_Utils_Type::T_FLOAT,
            'title'         => "Location Geo-Code longitude",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
        $fields_collection->setFieldSpec('location_geo_code_2', [
            'name'          => 'location_geo_code_2',
            'type'          => CRM_Utils_Type::T_FLOAT,
            'title'         => "Location Geo-Code latitude",
            'localizable'   => 0,
            'is_core_field' => false,
        ]);
    }
}
