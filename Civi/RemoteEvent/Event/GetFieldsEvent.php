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

declare(strict_types = 1);

namespace Civi\RemoteEvent\Event;

use Civi\RemoteEvent;

/**
 * Class GetFieldsEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This event will be triggered to populate the reply of the
 *    RemoteEvent.get_remote_event_fields field specs
 */
class GetFieldsEvent extends RemoteEvent {
  public const NAME = 'civi.remoteevent.getfields';

  /**
   * @var array holds the list of the RemoteEvent.get field specs */
  protected $field_specs;

  public function __construct($field_specs) {
    $this->field_specs = $field_specs;
  }

  /**
   * Get the current field specs
   *
   * @return array
   *   the key => spec list
   */
  public function getFieldSpecs() {
    return $this->field_specs;
  }

  /**
   * Set/add a particular field spec
   *
   * @param string $field_name
   *   the field name
   * @param array $spec
   *   the field spec
   */
  public function setFieldSpec($field_name, $spec) {
    $this->field_specs[$field_name] = $spec;
  }

  /**
   * Remove a particular field spec
   *
   * @param string $field_name
   *   the field name
   */
  public function removeFieldSpec($field_name) {
    unset($this->field_specs[$field_name]);
  }

  /**
   * Get the parameters of the original query
   *
   * @return array
   *   parameters of the query
   */
  public function getQueryParameters() {
    return [];
  }

}
