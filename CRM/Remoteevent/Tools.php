<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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
 * Some tools to be used by various parts of the system
 */
class CRM_Remoteevent_Tools
{
    /**
     * Get a localised list of option group values for the field keys
     *
     * @param string|integer $option_group_id
     *   identifier for the option group
     *
     * @return array list of key => (localised) label
     */
    public static function getOptions($option_group_id, $locale, $params = [], $use_name = false, $sort = 'weight asc')
    {
        $option_list = [];
        $query       = [
            'option.limit'    => 0,
            'option_group_id' => $option_group_id,
            'return'          => 'value,label,name',
            'is_active'       => 1,
            'sort'            => $sort,
        ];

        // extend/override query
        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }

        // check cache
        static $result_cache = [];
        $cache_key = serialize($query);
        if (isset($result_cache[$cache_key])) {
            $result = $result_cache[$cache_key];
        } else {
            // run query
            $result = civicrm_api3('OptionValue', 'get', $query);
            $result_cache[$cache_key] = $result;
        }

        // compile result
        $l10n   = CRM_Remoteevent_Localisation::getLocalisation($locale);
        foreach ($result['values'] as $entry) {
            if ($use_name) {
                $option_list[$entry['name']] = $l10n->localise($entry['label']);
            } else {
                $option_list[$entry['value']] = $l10n->localise($entry['label']);
            }
        }

        return $option_list;
    }
}
