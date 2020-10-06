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

use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * This is the test base class with lots of utility functions
 *
 * @group headless
 */
class CRM_Remoteevent_TestBase extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface,
                                                                            TransactionalInterface
{
    use Api3TestTrait {
        callAPISuccess as protected traitCallAPISuccess;
    }

    /** @var CRM_Core_Transaction current transaction */
    protected $transaction = null;

    public function setUpHeadless()
    {
        // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
        // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
        return \Civi\Test::headless()
            ->install(['de.systopia.xcm'])
            ->install(['de.systopia.identitytracker'])
            ->install(['de.systopia.remotetools'])
            ->installMe(__DIR__)
            ->apply();
    }

    public function setUp()
    {
        parent::setUp();
        $this->transaction = new CRM_Core_Transaction();
    }

    public function tearDown()
    {
        $this->transaction->rollback();
        $this->transaction = null;
        parent::tearDown();
    }

    /**
     * Create a new remote event. All of the
     *  vital fields will have default values, that can be overwritten by
     *  the array passed
     *
     * @param array $event_details
     */
    public function createRemoteEvent($event_details)
    {
        // prepare event
        $event_data = [
            'title'                                                         => "Event " . microtime(),
            'event_type_id'                                                 => 1,
            'start_date'                                                    => date('Y-m-d', strtotime('tomorrow')),
            'default_role_id'                                               => 1,
            'is_active'                                                     => 1,
            'event_remote_registration.remote_registration_enabled'         => 1,
            'event_remote_registration.remote_disable_civicrm_registration' => 1,
            'event_remote_registration.remote_registration_default_profile' => 'Standard1',
            'event_remote_registration.remote_registration_profiles'        => ['Standard1'],
        ];
        foreach ($event_details as $key => $value) {
            $event_data[$key] = $value;
        }
        CRM_Remoteevent_CustomData::resolveCustomFields($event_data);

        // create event and reload
        $result = $this->traitCallAPISuccess('Event', 'create', $event_data);
        $event = $result = $this->traitCallAPISuccess('Event', 'getsingle', ['id' => $result['id']]);
        CRM_Remoteevent_CustomData::labelCustomFields($event);

        return $event;
    }
}
