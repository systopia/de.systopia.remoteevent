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
    cj("[name^=session_ids]")
      .select2({placeholder: "loading sessions, please wait..."})
      .find("option")
      .remove();
    cj("[name^=session_ids]").change();

    // then reload
    let event_id = cj("input[name=event_id]").val();
    if (event_id) {
      CRM.api3('Session', 'get', {
        event_id: event_id
      }).done(function(result) {
        if (result.is_error) {
          // there has been a problem
          CRM.alert(
            result.error_message,
            "API Error",
            'error'
          );

        } else {
          // fill values
          for (let session_id in result.values) {
            cj("[name^=session_ids]")
              .select2({placeholder: ""})
              .append(`<option value="`+ session_id +`">`+ result.values[session_id]['title'] +`</option>`);
          }

          // pre-select the last values
          let last_values_string = cj("input[name=last_session_ids]").val();
          if (last_values_string) {
            let last_values = last_values_string.split(",");
            cj("[name^=session_ids]").val(last_values);
            cj("input[name=last_session_ids]").val(''); // clear value
          }

          // update element
          cj("[name^=session_ids]").change();
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
