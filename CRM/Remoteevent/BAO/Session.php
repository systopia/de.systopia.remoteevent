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

    /**
     * Copy/clone all the sessions from a given event to another.
     * This is usually triggered when copying an event
     *
     * @param integer $old_event_id
     * @param integer $new_event_id
     */
  public static function copySessions($old_event_id, $new_event_id)
  {
      $old_event_id = (int) $old_event_id;
      $new_event_id = (int) $new_event_id;
      self::executeQuery("
       INSERT INTO civicrm_session (event_id,title,is_active,start_date,end_date,slot_id,category_id,type_id,description,max_participants,location,presenter_id,presenter_title)
       SELECT {$new_event_id} AS event_id,title,is_active,start_date,end_date,slot_id,category_id,type_id,description,max_participants,location,presenter_id,presenter_title
       FROM civicrm_session
       WHERE event_id = {$old_event_id}
      ");
  }
}
