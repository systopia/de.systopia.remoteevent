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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Subscriber
 *
 * @package Civi\RemoteEvent\Event
 *
 * This will handle all subscriptions
 */
class Subscriber implements EventSubscriberInterface
{
    /**
     * Subscribe to the list events, so we can plug the built-in ones
     */
    public static function getSubscribedEvents()
    {
        return [
            'civi.xdedupe.finders'   => ['addBuiltinFinders', Events::W_MIDDLE],
            'civi.xdedupe.filters'   => ['addBuiltinFilters', Events::W_MIDDLE],
            'civi.xdedupe.resolvers' => ['addBuiltinResolvers', Events::W_MIDDLE],
            'civi.xdedupe.pickers'   => ['addBuiltinPickers', Events::W_MIDDLE],
        ];
    }

}
