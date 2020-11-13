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
<table class="remote-session remote-session-info">
  <tr>
    <td>{ts}Title{/ts}</td>
    <td>{$session.title}</td>
  </tr>
  <tr>
    <td>{ts}Restrictions{/ts}</td>
    <td>{if $session.max_participants}{ts 1=$session.max_participants}Up to %1 participants{/ts}{else}{ts}None{/ts}{/if}</td>
  </tr>
  {if $session.presenter_id}
  <tr>
    <td>{if $session.presenter_title}{$session.presenter_title}{else}{ts}Presenter{/ts}{/if}</td>
    <td>{crmAPI var='presenter' entity='Contact' action='getvalue' sequential=0 return="display_name" id=$session.presenter_id}{$presenter}</td>
  </tr>
  {/if}
  <tr>
    <td>{ts}Category{/ts}</td>
    <td>{$session.category}</td>
  </tr>
  <tr>
    <td>{ts}Type{/ts}</td>
    <td>{$session.type}</td>
  </tr>
  <tr>
    <td>{ts}Location{/ts}</td>
    <td>{$session.location}</td>
  </tr>
  <tr>
    <td>{ts}Details{/ts}</td>
    <td>{$session.description}</td>
  </tr>
</table>
