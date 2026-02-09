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

namespace Civi;

/**
 * Class RemoteParamsEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * This is the base for all API parameter manipulation events
 */
abstract class RemoteParamsEvent extends RemoteEvent {
  /**
   * @var integer|false|null remote contact ID if this is a personalised query */
  protected $remote_contact_id;

  public function __construct($params) {
    $this->request  = $params;
    $this->original_request = $params;
    // i.e. not looked up yet
    $this->remote_contact_id = FALSE;
  }

  /**
   * Set a parameter for the current parameters
   *
   * @param string $key
   *    parameter key
   * @param mixed $value
   *    parmeter value
   */
  public function setParameter($key, $value) {
    $this->request[$key] = $value;
  }

  /**
   * Remove / unset a parameter for the current parameters
   *
   * @param string $key
   *    parameter key
   */
  public function removeParameter($key) {
    unset($this->request[$key]);
  }

  /**
   * Returns the original parameters that were submitted to RemoteEvent.get
   *
   * @return array original parameters
   */
  public function getOriginalParameters() {
    return $this->original_request;
  }

  /**
   * Returns the current (manipulated) parameters to be submitted to Event.get
   *
   * @return array current parameters
   */
  public function getParameters() {
    return $this->request;
  }

  /**
   * Returns the current (manipulated) parameter
   *
   * @param string $key
   *   the parameter key
   *
   * @return mixed|null
   */
  public function getParameter($key) {
    return $this->request[$key] ?? NULL;
  }

  /**
   * Get the parameters of the original query
   *
   * @return array
   *   parameters of the query
   */
  public function getQueryParameters() {
    return $this->request;
  }

  public function getLimit() {
    // check the options array
    if (isset($this->request['options']['limit'])) {
      return (int) $this->request['options']['limit'];
    }

    // check the old-fashioned parameter style
    if (isset($this->request['option.limit'])) {
      return (int) $this->request['option.limit'];
    }

    // default is '25' (by general API contract)
    return 25;
  }

  public function getOffset() {
    // check the options array
    if (isset($this->request['options']['offset'])) {
      return (int) $this->request['options']['offset'];
    }

    // check the old-fashioned parameter style
    if (isset($this->request['option.offset'])) {
      return (int) $this->request['option.offset'];
    }

    // default is '0'
    return 0;
  }

  /**
   * Get the limit parameter of the original reuqest
   *
   * @return integer
   *   returned result count or 0 for 'no limit'
   */
  public function getOriginalLimit() {
    // check the options array
    if (isset($this->original_request['options']['limit'])) {
      return (int) $this->original_request['options']['limit'];
    }

    // check the old-fashioned parameter style
    if (isset($this->original_request['option.limit'])) {
      return (int) $this->original_request['option.limit'];
    }

    // default is '25' (by general API contract)
    return 25;
  }

  /**
   * Get the offset parameter of the original request
   *
   * @return integer
   *   returned result count or 0 for 'no offset'
   */
  public function getOriginalOffset() {
    // check the options array
    if (isset($this->original_request['options']['offset'])) {
      return (int) $this->original_request['options']['offset'];
    }

    // check the old-fashioned parameter style
    if (isset($this->original_request['option.offset'])) {
      return (int) $this->original_request['option.offset'];
    }

    // default is '0'
    return 0;
  }

  /**
   * Set the query limit
   *
   * @param $limit integer
   *   the new query limit
   */
  public function setLimit($limit) {
    unset($this->request['option.limit']);
    unset($this->request['options']['limit']);
    $this->request['option.limit'] = (int) $limit;
  }

  /**
   * Set the query offset
   *
   * @param $offset integer
   *   the new query offset
   */
  public function setOffset($offset) {
    unset($this->request['option.offset']);
    unset($this->request['options']['offset']);
    $this->request['option.offset'] = (int) $offset;
  }

}
