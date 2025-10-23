<?php
use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Session.create API specification (optional).
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_session_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * Session.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_session_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Session');
}

/**
 * Session.delete API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_session_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Session.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_session_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Session');
}
