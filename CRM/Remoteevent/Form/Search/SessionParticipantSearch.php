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
 * Search contacts based on event session participation
 */
// phpcs:ignore Generic.Files.LineLength.TooLong
class CRM_Remoteevent_Form_Search_SessionParticipantSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  public function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   *
   * @return void
   */
  public function buildForm(&$form) {
    CRM_Utils_System::setTitle(E::ts('Session Participant Search'));

    $form->addEntityRef(
        'event_id',
        E::ts('Event'),
        [
          'entity' => 'Event',
          'api' => [
            'params' => [
              'limit' => 0,
            ],
          ],
        ],
        TRUE
    );

    $form->add(
        'select',
        'session_ids',
        E::ts('Sessions'),
        [],
        FALSE,
        ['class' => 'crm-select2 huge', 'multiple' => 'multiple']
    );

    $form->add(
        'hidden',
        'last_session_ids'
    );
    $form->_submitValues['last_session_ids'] = implode(',', $this->getSessionIds());

    /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
    $form->assign('elements', ['event_id', 'session_ids', 'last_session_ids']);

    $slots = CRM_Remoteevent_Form_EventSessions::getSlots();
    Civi::resources()->addVars('remoteevent_slots', $slots);
    Civi::resources()->addScriptUrl(E::url('js/session_participant_search.js'));
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  public function &columns() {
    // return by reference
    $columns = [
      E::ts('Contact ID') => 'contact_id',
      E::ts('Name')       => 'sort_name',
      E::ts('Event')      => 'event_title',
      E::ts('Session')    => 'session_title',
    ];
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string, sql
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $query = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, 'GROUP BY contact_a.id');
    return $query;
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  public function select() {
    return "
          contact_a.id                               AS contact_id,
          contact_a.sort_name                        AS sort_name,
          event.title                                AS event_title,
          GROUP_CONCAT(session.title SEPARATOR ', ') AS session_title
        ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  public function from() {
    return '
          FROM civicrm_contact contact_a
          LEFT JOIN civicrm_participant participant
                 ON participant.contact_id = contact_a.id
          LEFT JOIN civicrm_event event
                 ON event.id = participant.event_id
          LEFT JOIN civicrm_participant_session participant_session
                 ON participant_session.participant_id = participant.id
          LEFT JOIN civicrm_session session
                 ON session.id = participant_session.session_id
        ';
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   *
   * @return string, sql fragment with conditional expressions
   */
  public function where($includeContactIDs = FALSE) {
    $params = [];
    $wheres = [];

    // make sure contact is not deleted
    $wheres[] = '(contact_a.is_deleted IS NULL OR contact_a.is_deleted = 0)';

    // add event_id clause
    $event_id = (int) $this->_formValues['event_id'] ?? NULL;
    if ($event_id) {
      $wheres[] = "event.id = {$event_id}";
    }

    // add session_ids clause
    $session_id_list = implode(',', $this->getSessionIds());
    if ($session_id_list) {
      $wheres[] = "session.id IN ({$session_id_list})";
    }
    else {
      $wheres[] = 'session.id IS NOT NULL';
    }

    if (empty($wheres)) {
      return 'TRUE';
    }
    else {
      $where = '(' . implode(') AND (', $wheres) . ')';
      return $this->whereClause($where, $params);
    }
  }

  /**
   * @return mixed
   */
  public function count() {
    $sql = $this->all();
    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   *
   * @return void
   */
  public function alterRow(&$row) {}

  /**
   * Get the submitted session IDs. This is tricky,
   *  since the values are dynamic (depending on the event)
   *  and will be filtered out by the systen
   *
   * @return array
   *   list of session IDs
   */
  protected function getSessionIds() {
    if (!empty($this->_formValues['session_ids'])) {
      $session_ids = $this->_formValues['session_ids'];
    }
    else {
      $session_ids = $_REQUEST['session_ids'] ?? NULL;
    }
    if (empty($session_ids)) {
      $session_ids = [];
    }
    elseif (is_array($session_ids)) {
      $session_ids = array_map('intval', $session_ids);
    }
    else {
      $session_ids = [(int) $session_ids];
    }
    return $session_ids;
  }

}
