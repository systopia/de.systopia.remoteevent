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
 * Collection of upgrade steps.
 */
class CRM_Remoteevent_Upgrader extends CRM_Remoteevent_Upgrader_Base
{

    /**
     * Create the required custom data
     */
    public function install()
    {
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/option_group_remote_registration_profiles.json'));
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        $customData->syncOptionGroup(E::path('resources/option_group_remote_contact_roles.json'));
    }

    /**
     * Adding roles
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0001()
    {
        $this->ctx->log->info('Adding remote roles.');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/option_group_remote_contact_roles.json'));
        return true;
    }

    /**
     * Adding instant invitation field
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0002()
    {
        $this->ctx->log->info('Adding instant invitation field.');
        $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_remote_registration.json'));
        return true;
    }
}
