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


{foreach from=$sessions key=day item=day_sessions}
<table class="remote-session remote-session-day">
  <caption>{$day}</caption>
  <thead>
    <tr>
      <td></td>
      <th>{ts}Category{/ts}</th>
      <th>{ts}Type{/ts}</th>
      <th>{ts}Time{/ts}</th>
      <th>{ts}Title{/ts}</th>
      <th>{ts}Registrations{/ts}</th>
      <th>{ts}Info{/ts}</th>
      <th>{ts}Actions{/ts}</th>
    </tr>
  </thead>

  <tbody>
    {foreach from=$day_sessions key=slot item=slot_sessions name=slot_sessions}
      {foreach from=$slot_sessions item=session name=sessions}
      <tr>
      {if $smarty.foreach.sessions.first}
        <th rowspan="8">{$slots.$slot}</th>
      {/if}
      <tr class="remote-session remote-session-session {foreach from=$session.classes item=htmlclass}{$htmlclass}{/foreach}">
        <td>{$session.category}</td>
        <td>{$session.type}</td>
        <td>{$session.time}</td>
        <td>{$session.title}</td>
        <td>{$session.participants}</td>
        <td>{foreach from=$session.icons item=icon}{$icon} {/foreach}</td>
        <td><span>{foreach from=$session.actions item=action}{$action}{/foreach}</span></td>
      </tr>
      {/foreach}
    {/foreach}
  </tbody>
</table>
{/foreach}

{*

classes:
odd/even
field-name
attribute
is_full
hat beschränkung

caption in table

<table><!-- A table for each day -->

  <caption>Veranstaltungstag 1</caption><!-- A caption for each table -->

  <thead>
  <tr>
    <td></td>
    <th>Feld 1</th>
    <th>Feld 2</th>
    <th>Feld 3</th>
  </tr>
  </thead>

  <tbody>
  <tr>
    <th rowspan="3">Slot 1</th><!-- rowspan is no. of data cells in a row -->
    <td>Inhalt Feld 1</td>
    <td>Inhalt Feld 2</td>
    <td>Inhalt Feld 3</td>
  </tr>
  <tr>
    <td>Inhalt Feld 1</td>
    <td>Inhalt Feld 2</td>
    <td>Inhalt Feld 3</td>
  </tr>
  <tr>
    <td>Inhalt Feld 1</td>
    <td>Inhalt Feld 2</td>
    <td>Inhalt Feld 3</td>
  </tr>
  </tbody><!-- a tbody element for each slot -->
  <tbody>
  <tr>
    <th rowspan="3">Slot 2</th><!-- rowspan is no. of data cells in a row -->
    <td>Inhalt Feld 1</td>
    <td>Inhalt Feld 2</td>
    <td>Inhalt Feld 3</td>
  </tr>
  <tr>
    <td>Inhalt Feld 1</td>
    <td>Inhalt Feld 2</td>
    <td>Inhalt Feld 3</td>
  </tr>
  <tr>
    <td>Inhalt Feld 1</td>
    <td>Inhalt Feld 2</td>
    <td>Inhalt Feld 3</td>
  </tr>
  </tbody>

</table>
*}