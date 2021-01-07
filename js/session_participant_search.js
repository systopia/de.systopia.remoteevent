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

cj(document).ready(function() {

  /**
   * Will refill the session list based on the event_id
   */
  function update_session_list() {
    // first: clear dropdown
    cj('#event_id').empty().multiSelect('refresh');

    // then reload
    let event_id = cj("input[name=event_id]").val();
    if (event_id) {
      CRM.api3('Session', 'get', {
        event_id: event_id
      }).done(function(result) {
        if (result.is_error) {
          onError(result.error_message, null);
        } else {
          console.log(result.values);
        }
      });
    }
  }

  // trigger now
  update_session_list();

  // ... and when the event changed
  cj("input[name=event_id]").change(function() {
    update_session_list();
  });
});