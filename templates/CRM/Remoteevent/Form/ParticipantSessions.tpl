{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.remoteevent'}
<div class="remote-session remote-session-main-container">
<table class="remote-session remote-session-day">
  <h2>{$event_header}</h2>
  {foreach from=$sessions key=day item=day_sessions}
  {if $day_sessions|@count gt 0}
    <caption class="remote-session remote-session-day">{$day}</caption>
    <thead>
      <tr>
        <th></th>
        <th>{ts}Title{/ts}</th>
        <th>{ts}Category{/ts}</th>
        <th>{ts}Type{/ts}</th>
        <th>{ts}Time{/ts}</th>
        <th>{ts}Participants{/ts}</th>
        <th>{ts}Registered?{/ts}</th>
      </tr>
    </thead>

    <tbody>
      {foreach from=$day_sessions key=slot item=slot_sessions name=slot_sessions}
        {foreach from=$slot_sessions item=session name=sessions}
        <tr class="remote-session remote-session-session {cycle values="odd-row,even-row"} {foreach from=$session.classes item=htmlclass}{$htmlclass}{/foreach}">
        {if $smarty.foreach.sessions.first}
          <th rowspan="{$slot_sessions|@count}">{$slots.$slot}</th>
        {/if}
          {capture assign=session_field}session{$session.id}{/capture}
          <td class="remote-session remote-session-title" title="{$session.description_text}">{$session.title}</td>
          <td class="remote-session remote-session-category">{$session.category}</td>
          <td class="remote-session remote-session-type">{$session.type}</td>
          <td class="remote-session remote-session-time" title="{$session.duration}">{$session.time}</td>
          <td class="remote-session remote-session-participants">{$session.participants}</td>
          <td class="remote-session remote-session-registered">{$form.$session_field.html}</td>
        </tr>
        {/foreach}
      {/foreach}
    </tbody>
  {/if}
  {/foreach}
</table>

  <br/>

  <div class="crm-section">
    <div class="label">{$form.bypass_restriction.label}&nbsp;{help id="id-bypass" title=$form.bypass_restriction.label}</div>
    <div class="content">{$form.bypass_restriction.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/crmScope}