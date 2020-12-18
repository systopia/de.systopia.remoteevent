{*-------------------------------------------------------+
| SYSTOPIA Remote Tools                                  |
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
  <h3>{ts}Participant Changes{/ts}</h3>
  <table class="remote-participant-changes">
    <thead>
      <tr>
        <th>{ts}Attribute{/ts}</th>
        <th>{ts}Previous Value{/ts}</th>
        <th>{ts}New Value{/ts}</th>
      </tr>
    </thead>
    <tbody>
    {foreach from=$diff_data item=diff_item}
      <tr>
        <td>{$diff_item.label}</td>
        <td>{$diff_item.old_value}</td>
        <td>{$diff_item.new_value}</td>
      </tr>
    {/foreach}
    </tbody>
  </table>
{/crmScope}