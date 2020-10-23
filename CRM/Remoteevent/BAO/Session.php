<?php
use CRM_Remoteevent_ExtensionUtil as E;

class CRM_Remoteevent_BAO_Session extends CRM_Remoteevent_DAO_Session {

  /**
   * Create a new Session based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Remoteevent_DAO_Session|NULL
   */
  public static function create($params) {
    $className = 'CRM_Remoteevent_DAO_Session';
    $entityName = 'Session';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }
}
