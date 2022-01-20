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

/**
 * Delete session function
 * @param session_id
 * @param confirmed
 */
function remote_session_delete(session_id, confirmed)
{
  let ts = CRM.ts('de.systopia.remoteevent');
  if (confirmed) {
    CRM.api3('Session', 'delete', {id:session_id})
      .done(CRM.alert(ts("Session [%1] was deleted.", {1:session_id}), ts("Session deleted")))
  } else {
    CRM.confirm({
      message: ts("Do you really want to delete session [%1]?", {1:session_id}),
    }).on('crmConfirm:yes', function() {
      remote_session_delete(session_id, true);

      // Refresh the tab.
      cj('#' + cj("#tab_sessions").attr('aria-controls'))
        .crmSnippet('refresh');
    });
  }
}

cj(document).ready(function () {
  // make sure we reload after a popup closes
  cj(document)
    .off('crmPopupFormSuccess.remoteevent_sessions')
    .on('crmPopupFormSuccess.remoteevent_sessions', function (event) {
      // gray out existing form
      cj("div.remote-session-main-container").addClass("disabled");

      // Refresh the tab.
      cj('#' + cj("#tab_sessions").attr('aria-controls'))
        .crmSnippet('refresh');
    });
});
