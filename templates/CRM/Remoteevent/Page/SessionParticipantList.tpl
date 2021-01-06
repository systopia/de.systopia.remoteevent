{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.remoteevent'}
<table class="remote-session-participants">
  <thead>
  <tr>
    <th>{ts}Name{/ts}</th>
    <th>{ts}Status{/ts}</th>
    <th>{ts}Role{/ts}</th>
    <th></th>
  </tr>
  </thead>

  <tbody>
    {foreach from=$participants item=participant}
      <tr class="remote-session-participants {cycle values="odd-row,even-row"}">
        <td class="remote-session-participants remote-session-participants-name"><a href="{$participant.contact_link}">{$participant.display_name}</a></td>
        <td class="remote-session-participants remote-session-participants-status">{$participant.participant_status}</td>
        <td class="remote-session-participants remote-session-participants-role">{$participant.participant_role}</td>
        <td class="remote-session-participants remote-session-participants-links">{$participant.link}</td>
      </tr>
    {/foreach}
  </tbody>
</table>
{/crmScope}