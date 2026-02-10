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

declare(strict_types = 1);

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Some very basic tests around CiviRemote Event
 *
 * @group headless
 * @coversNothing
 *   TODO: Document actual coverage.
 */
class CRM_Remoteevent_EventCustomFieldTest extends CRM_Remoteevent_TestBase {

  /**
   * Search a set of values in a single value custom fields
   */
  public function testRemoteEventGetCustom() {
    // create custom group
    $customData = new CRM_Remoteevent_CustomData(E::LONG_NAME);
    $customData->syncOptionGroup(E::path('tests/resources/option_group_age_range.json'));
    $customData->syncCustomGroup(E::path('tests/resources/custom_group_event_test2.json'));

    // create decoy events
    $decoy1 = $this->createRemoteEvent();
    $decoy2 = $this->createRemoteEvent();
    $decoy3 = $this->createRemoteEvent();
    $decoy4 = $this->createRemoteEvent();

    // create an event with custom fields
    $event = $this->createRemoteEvent(
      [
        'title' => 'Event with values',
        'event_test2.event_single_test' => '2',
        'event_test2.event_multi_test' => [2, 3],
      ]
    );

    // SINGLE VALUE CUSTOM FIELD
    // run a simple search for the custom field
    $search_result = $this->traitCallAPISuccess(
      'RemoteEvent',
      'get',
      [
        'option.limit' => 2,
        'event_test2.event_single_test' => '2',
      ]
    );
    $this->assertArrayHasKey('id', $search_result, 'The event could not be found by single custom field search');
    $this->assertEquals(
      $event['id'],
      $search_result['id'],
      'The event could not be found by single custom field search'
    );

    // run a simple search for the custom field
    $search_result = $this->traitCallAPISuccess(
      'RemoteEvent',
      'get',
      [
        'option.limit' => 2,
        'event_test2.event_single_test' => ['IN' => [2, 3, 4]],
      ]
    );
    $this->assertArrayHasKey('id', $search_result, 'The event could not be found by single custom field range search');
    $this->assertEquals(
      $event['id'],
      $search_result['id'],
      'The event could not be found by single custom field range search'
    );

    // MULTI VALUE CUSTOM FIELD
    // run a simple search for the custom field
    $search_result = $this->traitCallAPISuccess(
      'RemoteEvent',
      'get',
      [
        'option.limit' => 2,
        'event_test2.event_multi_test' => ['IN' => [2]],
      ]
    );
    $this->assertArrayHasKey(
      'id',
      $search_result,
      'The event could not be found by multi custom field search'
    );
    $this->assertEquals(
      $event['id'],
      $search_result['id'],
      'The event could not be found by multi custom field search'
    );

    // run a simple search for the custom field
    $search_result = $this->traitCallAPISuccess(
      'RemoteEvent',
      'get',
      [
        'option.limit' => 3,
        'event_test2.event_multi_test' => ['IN' => [2, 3]],
      ]
    );
    $this->assertArrayHasKey('id', $search_result, 'The event could not be found by multi custom field range search');
    $this->assertEquals(
      $event['id'],
      $search_result['id'],
      'The event could not be found by multi custom field range search'
    );

    // run a simple search for the custom field
    $search_result = $this->traitCallAPISuccess(
      'RemoteEvent',
      'get',
      [
        'option.limit' => 3,
        'event_test2.event_multi_test' => ['IN' => [3, 2]],
      ]
    );
    $this->assertArrayHasKey('id', $search_result, 'The event could not be found by multi custom field range search');
    $this->assertEquals(
      $event['id'],
      $search_result['id'],
      'The event could not be found by multi custom field range search'
    );

    // run a simple search for the custom field
    $search_result = $this->traitCallAPISuccess(
      'RemoteEvent',
      'get',
      [
        'option.limit' => 3,
        'event_test2.event_multi_test' => ['IN' => [2, 3, 4]],
      ]
    );
    $this->assertArrayHasKey('id', $search_result, 'The event could not be found by multi custom field range search');
    $this->assertEquals(
      $event['id'],
      $search_result['id'],
      'The event could not be found by multi custom field range search'
    );

    // run a search for the custom field with the precise values
    $search_result = $this->traitCallAPISuccess(
      'RemoteEvent',
      'get',
      [
        'option.limit' => 3,
        'event_test2.event_multi_test' => [2, 3],
      ]
    );
    $this->assertArrayHasKey('id', $search_result, 'The event could not be found by multi custom field range search');
    $this->assertEquals(
      $event['id'],
      $search_result['id'],
      'The event could not be found by multi custom field range search'
    );

    // run a search for the custom field field with more than the precise values
    $search_result = $this->traitCallAPISuccess(
      'RemoteEvent',
      'get',
      [
        'option.limit' => 3,
        'event_test2.event_multi_test' => [2, 3, 4],
      ]
    );
    $this->assertEmpty(
      $search_result['id'] ?? NULL,
      'The should could not be found by this multi custom field equals operation'
    );
  }

}
