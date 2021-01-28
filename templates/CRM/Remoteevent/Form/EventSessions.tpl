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
<div class="remote-session remote-session-main-container">

<div class="remote-session remote-button-section">
  <a class="button remote-session remote-session-add crm-popup" href="{$add_session_link}"><span><i class="crm-i fa-plus" aria-hidden="true"></i>&nbsp;{ts}Add Session{/ts}</span></a>
  <a class="button remote-session remote-session-csv" href="{$download_session_link}"><span><i class="crm-i fa-table" aria-hidden="true"></i>&nbsp;{ts}Download as CSV{/ts}</span></a>
</div>

{foreach from=$sessions key=day item=day_sessions}
{if $day_sessions|@count gt 0}
<table class="remote-session remote-session-day">
  <caption class="remote-session remote-session-day">{$day}</caption>
  <thead>
    <tr>
      <th></th>
      <th>{ts}Category{/ts}</th>
      <th>{ts}Type{/ts}</th>
      <th>{ts}Time{/ts}</th>
      <th>{ts}Title{/ts}</th>
      <th>{ts}Participants{/ts}</th>
      <th>{ts}Info{/ts}</th>
      <th>{ts}Actions{/ts}</th>
    </tr>
  </thead>

  <tbody>
    {foreach from=$day_sessions key=slot item=slot_sessions name=slot_sessions}
      {foreach from=$slot_sessions item=session name=sessions}
      <tr class="remote-session remote-session-session {cycle values="odd-row,even-row"} {foreach from=$session.classes item=htmlclass}{$htmlclass}{/foreach}">
      {if $smarty.foreach.sessions.first}
        <th rowspan="{$slot_sessions|@count}">{$slots.$slot}</th>
      {/if}
        <td class="remote-session remote-session-category">{$session.category}</td>
        <td class="remote-session remote-session-type">{$session.type}</td>
        <td class="remote-session remote-session-time" title="{$session.duration}">{$session.time}</td>
        <td class="remote-session remote-session-title" title="{$session.description_text}">{$session.title}</td>
        <td class="remote-session remote-session-participants">{$session.participants}</td>
        <td class="remote-session remote-session-icons">{foreach from=$session.icons item=icon}{$icon} {/foreach}</td>
        <td><span>{foreach from=$session.actions item=action}{$action}{/foreach}</span></td>
      </tr>
      {/foreach}
    {/foreach}
  </tbody>
</table>
{/if}
{/foreach}
</div>
{/crmScope}