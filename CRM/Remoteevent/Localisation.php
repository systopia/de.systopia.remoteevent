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
 * Localisation tools to localise the event data.
 */
class CRM_Remoteevent_Localisation
{
    /** @var array caches the localisation instances */
    static $instances = [];

    /** @var string the locale used by this instance */
    protected $locale = null;

    protected function __construct($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get a localisation instance
     *
     * @param string $locale
     *   the locale to use. Default's to no localisation,
     *   use 'default' for CiviCRM's current locale
     *
     * @return CRM_Remoteevent_Localisation
     */
    public static function getLocalisation($locale = null)
    {
        // default to current locale
        if ($locale == 'default') {
            $locale = CRM_Core_I18n::getLocale();
        }

        if (!isset(self::$instances[$locale])) {
            self::$instances[$locale] = new CRM_Remoteevent_Localisation($locale);
        }
        return self::$instances[$locale];
    }

    /**
     * Localise a given string with this localisation
     *
     * @param string $string
     *   the string to localise
     *
     * @param array $context
     *   localisation parameters or variables
     *
     * @return string
     *   localised result
     *
     * @see \ts()
     *
     * @deprecated 1.2.0 Deprecated in favor of self::ts().
     * @see self::ts()
     */
    public function localise($string, $context = [])
    {
        return self::ts($string, $context);
    }

    /**
     * Localise a given string with this localisation.
     *
     * @param string $string
     *   The (English) string to localise.
     *
     * @param array $context
     *   Localisation parameters or variables.
     *
     * @return string
     *   The localised version of the string.
     *
     * @see \ts()
     */
    public function ts($string, $context = []) {
        if (empty($this->locale)) {
            // No changes, used for pot extraction.
            return $string;
        }
        else {
            $currentLocale = CRM_Core_I18n::getLocale();
            $locale = CRM_Core_I18n::singleton();
            $locale->setLocale($this->locale);
            $localizedString = E::ts($string, $context);
            $locale->setLocale($currentLocale);
            return $localizedString;
        }
    }
}
