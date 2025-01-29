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
{assign var=style_odd value="odd"}{assign var=style_even value="even"}
<table class="civiremote-event-session-info">
  <tr class="civiremote-event-session-info-row civiremote-event-session-info-row-title {$style_odd}">
    <th>{ts}Title{/ts}</th>
    <td>{$session.title}</td>
  </tr>
  <tr class="civiremote-event-session-info-row civiremote-event-session-info-row-restrictions {$style_even}">
    <th>{ts}Restrictions{/ts}</th>
    <td>{if $session.max_participants}{ts 1=$session.max_participants}Up to %1 participants{/ts}{else}{ts}None{/ts}{/if}</td>
  </tr>
  {if $session.presenter_id}
  <tr class="civiremote-event-session-info-row civiremote-event-session-info-row-presenter {$style_odd}">
    <th>{if $session.presenter_title}{$session.presenter_title}{else}{ts}Presenter{/ts}{/if}</th>
    <td>{$session.presenter_display_name}</td>
    {* swap following styles *}{assign var=style_odd value="even"}{assign var=style_even value="odd"}
  </tr>
  {/if}
  <tr class="civiremote-event-session-info-row civiremote-event-session-info-row-category {$style_odd}">
    <th>{ts}Category{/ts}</th>
    <td>{$session.category}</td>
  </tr>
  <tr class="civiremote-event-session-info-row civiremote-event-session-info-row-type {$style_even}">
    <th>{ts}Type{/ts}</th>
    <td>{$session.type}</td>
  </tr>
  <tr class="civiremote-event-session-info-row civiremote-event-session-info-row-location {$style_odd}">
    <th>{ts}Location{/ts}</th>
    <td>{$session.location}</td>
  </tr>
  <tr class="civiremote-event-session-info-row civiremote-event-session-info-row-details {$style_even}">
    <th>{ts}Details{/ts}</th>
    <td>{$session.description}</td>
  </tr>
</table>
{/crmScope}
