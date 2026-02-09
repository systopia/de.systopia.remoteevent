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

use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Some very basic tests around CiviRemote Event
 *
 * @group headless
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_Remoteevent_CreateEventTest extends CRM_Remoteevent_TestBase
{
    /**
     * Search a set of values in a single value custom fields
     */
    public function testSimpleCreateEvent()
    {
        // just run a simple comparison
        $test_internal = $this->createRemoteEvent();
        $test_via_spawn = $this->createRemoteEvent([], true);
        $this->assertEquals(
            array_keys($test_internal),
            array_keys($test_via_spawn),
            "The internal and external creation produce different results"
        );
    }

    /**
     * Search a set of values in a single value custom fields
     */
    public function testCreateTemplateEvent()
    {
        // first create a template
        $template = $this->createRemoteEvent();
        $this->traitCallAPISuccess('Event', 'create',[
            'id'             => $template['id'],
            'is_template'    => 1,
            'template_title' => 'Test Template'
        ]);

        // create an new event from the template
        $spawned_from_template = $this->createRemoteEvent(['template_id' => $template['id'], 'title' => null], true);
        $this->assertEquals("Copy of {$template['title']}", $spawned_from_template['title'], "An event spawned from the template should have the same name");
    }
}
