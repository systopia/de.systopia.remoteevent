<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2022 SYSTOPIA                            |
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

namespace Civi;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class LabelEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * Render/Adjust labels
 */
class LabelEvent extends Event {
  public const NAME = 'civi.remoteevent.label';

  /**
   * @var string
   *   context for adjusting the session group header, e.g. "Day 1"
   *   WARNING: make sure not to use the same labels,
   *   as it *will* overwrite/drop the sessions previously under this label
   */
  public const CONTEXT_SESSION_GROUP_TITLE = 'remoteevent.session.groupheader';

  /**
   * @var string the render context identifier */
  protected $context;

  /**
   * @var string the label as proposed by the system */
  protected $original_label;

  /**
   * @var string the label to be used by the system */
  protected $accepted_label;

  /**
   * @var array context data for the label generation */
  protected $context_data;

  /**
   * Create a label rendering event
   *
   * @param string $original_label
   *   the label proposed by the system
   *
   * @param string $context
   *   the context string marking what the label is for
   *
   * @param array $context_data
   *   context data from the rendering environment, probably different for every context
   */
  protected function __construct($original_label, $context, $context_data = []) {
    $this->original_label = $original_label;
    $this->accepted_label = $original_label;
    $this->context_data = $context_data;
    $this->context = $context;
  }

  /**
   * Get the label as it's currently proposed
   *
   * @return string
   */
  public function getLabel() {
    return $this->accepted_label;
  }

  /**
   * Is this label for the backend (i.e. CiviCRM) or for remote diplay?
   *
   * @return boolean
   */
  public function isBackend() {
    if (isset($this->context_data['is_backend'])) {
      return (bool) $this->context_data['is_backend'];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Set/override the new label
   *
   * @param string $new_label
   *   the label as it should be
   */
  public function setLabel($new_label) {
    $this->accepted_label = $new_label;
  }

  /**
   * Get an attribute from the context information
   *
   * @param string $attribute_name
   *    name of the attribute
   *
   * @return string|null
   */
  public function getContextAttribute($attribute_name) {
    return $this->context_data[$attribute_name] ?? NULL;
  }

  /**
   * Check if this event has the given context identifier
   *
   * @param string $context
   *   the context identifier expected
   *
   * @return boolean
   *   returns true if the context of the event equals the expected context
   */
  public function isContext(string $context) {
    return $this->context == $context;
  }

  /**
   * Get the event context identifier
   *
   * @return string|null
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Get the label as it's originally proposed
   *
   * @return string
   */
  public function getOriginalLabel() {
    return $this->original_label;
  }

  /**
   * Utility function: adjust a label
   *
   * @param string $original_label
   *   the label proposed by the system
   *
   * @param string $context
   *   the context string marking what the label is for
   *
   * @param array $context_data
   *   context data from the rendering environment, probably different for every context
   *
   * @return string
   *   the rendered label
   */
  public static function renderLabel($original_label, $context, $context_data = []) {
    $label_event = new LabelEvent($original_label, $context, $context_data);
    \Civi::dispatcher()->dispatch(self::NAME, $label_event);
    return $label_event->getLabel();
  }

}
