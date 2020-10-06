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

use Remoteevent_ExtensionUtil as E;

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
            ->install(['de.systopia.identitytracker'])
            ->install(['de.systopia.xcm'])
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
}
